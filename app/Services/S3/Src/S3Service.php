<?php

namespace App\Services\S3\Src;

use App\Libraries\FileEncryption;
use App\Libraries\Helpers;
use Aws\CloudFront\CloudFrontClient;
use Aws\S3\S3Client;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Created by PhpStorm.
 * User: phuctran
 * Date: 19/01/2017
 * Time: 15:42
 */
class S3Service
{
    protected $_env_prefix;
    protected $_disk = 's3';
    protected $_storage;
    protected $_s3Client;
    protected $_s3Bucket;

    public function __construct()
    {
        $this->_env_prefix = env('S3_PREFIX_ENV', 'develop') . '/' . env('S3_PROJECT');
        $this->_storage = $this->getStorageAdapter();

        $s3Client = new S3Client([
            'region'      => env('S3_REGION'),
            'version'     => 'latest',
            'credentials' => [
                'key'    => env('S3_KEY'),
                'secret' => env('S3_SECRET')
            ]
        ]);
        $this->_s3Client = $s3Client;
        $this->_s3Bucket = env('S3_BUCKET');
    }

    protected function getStorageAdapter(): FilesystemAdapter
    {
        return Storage::disk($this->_disk);
    }

    protected function normalizePathS3(string $pathOnS3): string
    {
        return $this->_env_prefix . '/' . $pathOnS3;
    }

    protected function cancelNormalizePathS3(string $pathOnS3Real): string
    {
        $pathOnS3RealArray = explode('/', $pathOnS3Real);
        unset($pathOnS3RealArray[0]);
        unset($pathOnS3RealArray[1]);

        return implode('/', $pathOnS3RealArray);
    }

    /**
     * @param string $content
     *
     * @return string
     */
    protected function breakOrderBytesOfFile($content)
    {
        $byteArray = unpack('N*', $content);
        $byteArray = array_reverse($byteArray);
        $breakContent = pack('I*', ...$byteArray);

        return $breakContent;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    protected function restoreOrderBytesOfFile($content)
    {
        $byteArray = unpack('I*', $content);
        $byteArray = array_reverse($byteArray);
        $restoreContent = pack('N*', ...$byteArray);

        return $restoreContent;
    }

    /**
     * @param string      $pathOnS3
     * @param string      $pathFile
     * @param string|null $visibility
     *
     * @return false|string
     */
    public function upload(string $pathOnS3, string $pathFile, string $visibility = null, bool $encrypt = true)
    {
        return $this->uploadFile($pathOnS3, new File($pathFile), $visibility, $encrypt);
    }

    /**
     * @param string $pathOnS3
     * @param File|UploadedFile|string $srcFile
     * @param string|null $visibility
     *
     * @param bool $encrypt
     * @return false|string
     */
    public function uploadFile(string $pathOnS3, $srcFile, string $visibility = null, bool $encrypt = true)
    {
        $fileName = null;
        $fileExt = null;
        if(is_string($srcFile)){
            $srcFile = new File($srcFile);
        }
        if($srcFile instanceof File){
            $fileName = $srcFile->getFilename();
            $fileExt = $srcFile->getExtension();
        }
        else if ($srcFile instanceof UploadedFile){
            $fileName = $srcFile->getClientOriginalName();
            $fileExt = $srcFile->getClientOriginalExtension();
        }
        $upPath = $this->normalizePathS3($pathOnS3);

        $content = file_get_contents($srcFile->getRealPath());
        if ($encrypt) {
            $tempFile = storage_path().'/'.basename($fileName) . '.encrypt';
            FileEncryption::encryptFile($srcFile->getRealPath(), $tempFile);
            $content = file_get_contents($tempFile);
            unlink($tempFile);
        }

        //$upPath .= DIRECTORY_SEPARATOR . str_random(32) . '.' . $fileExt;

        $visibility = $visibility ?: 'public';

        $result = $this->_storage->put($upPath, $content, $visibility);
        if ($result !== false)
            return $this->cancelNormalizePathS3($upPath);
        else return false;
    }

    public function download(string $pathOnS3, string $pathOnLocal, bool $decrypt = true)
    {
        $content = $this->getContent($pathOnS3, $decrypt);
        if (!Helpers::isAbsolutePath($pathOnLocal)) {
            $pathOnLocal = storage_path($pathOnLocal);
        }
        file_put_contents($pathOnLocal, $content);
    }

    /**
     * @param string $pathOnS3
     *
     * @param bool $decrypt
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getContent(string $pathOnS3, bool $decrypt = true)
    {
        $pathOnS3Real = $this->normalizePathS3($pathOnS3);
        $content = $this->_storage->get($pathOnS3Real);
        $srcFile = storage_path().'/'.uniqid() . '.encrypt';
        file_put_contents($srcFile, $content);
        if ($decrypt) {
            $tempFile = storage_path().'/'.uniqid() . '.decrypt';
            FileEncryption::decryptFile($srcFile, $tempFile);
            $content = file_get_contents($tempFile);
            unlink($tempFile);
            unlink($srcFile);
        }
        return $content;
    }

    /**
     * @param string $pathOnS3
     *
     * @return bool
     */
    public function exists(string $pathOnS3)
    {
        $pathOnS3Real = $this->normalizePathS3($pathOnS3);

        return $this->_storage->exists($pathOnS3Real);
    }

    /**
     * @param string $pathOnS3
     *
     * @return bool
     */
    public function delete(string $pathOnS3)
    {
        $pathOnS3Real = $this->normalizePathS3($pathOnS3);

        return $this->_storage->delete($pathOnS3Real);
    }

    /**
     * @param string $pathOnS3
     *
     * @return string
     */
    public function getUrl(string $pathOnS3): string
    {
        $pathOnS3Real = $this->normalizePathS3($pathOnS3);

        return $this->_storage->url($pathOnS3Real);
    }

    /**
     * @param string $pathOnS3
     * @param int    $expTime
     *
     * @return string
     */
    public function getPreSignedUrl(string $pathOnS3, int $expTime = null): string
    {
        $expire = $expTime ?: $this->getExpire();
        $pathOnS3Real = $this->normalizePathS3($pathOnS3);
        $cmd = $this->_s3Client->getCommand('GetObject', [
            'Bucket' => $this->_s3Bucket,
            'Key'    => $pathOnS3Real
        ]);

        $request = $this->_s3Client->createPresignedRequest($cmd, $expire);

        return (string)$request->getUri();
    }

    /**
     * @param string $pathOnS3
     * @param int    $expTime
     *
     * @return string
     */
    public function getSignedUrl(string $pathOnS3, int $expTime = null): string
    {
        $cloudFront = new CloudFrontClient([
            'region'  => env('S3_REGION'),
            'version' => 'latest'
        ]);

        // Setup parameter values for the resource
        $streamHostUrl = env('S3_CLOUDFRONT_URL');
        $expire = $expTime ?: $this->getExpire();
        // Create a signed URL for the resource using the canned policy
        $signedUrlCannedPolicy = $cloudFront->getSignedUrl([
            'url'         => $streamHostUrl . '/' . $this->normalizePathS3($pathOnS3),
            'expires'     => $expire,
            'private_key' => base_path('key/cloudfront-private-key.pem'),
            'key_pair_id' => env('S3_CLOUDFRONT_KEYPAIR_ID')
        ]);
        return $signedUrlCannedPolicy;
    }

    /**
     * @return FilesystemAdapter
     */
    public function getStorage()
    {
        return $this->_storage;
    }

    private function getExpire(){
        return time() + (60 * 60 * 24 * env('S3_EXPIRE_DOWNLOAD_FILE', 1));
    }
}