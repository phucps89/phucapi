<?php

namespace App\Services\S3;

use App\Services\S3\Src\S3Service;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Facade;

/**
 * Created by PhpStorm.
 * Account: phuctran
 * Date: 19/01/2017
 * Time: 15:20
 */

/**
 * Class S3Facade
 *
 * @method static false|string upload(string $pathOnS3, string $pathFile, string $visibility = null, bool $encrypt = true)
 * @method static false|string uploadFile(string $pathOnS3, File $srcFile, string $visibility = null, bool $encrypt = true)
 * @method static void download(string $pathOnS3, string $pathOnLocal)
 * @method static string getContent(string $pathOnS3)
 * @method static bool exists(string $pathOnS3)
 * @method static string getUrl(string $pathOnS3)
 * @method static string getPreSignedUrl(string $pathOnS3, int $expTime = null)
 * @method static string getSignedUrl(string $pathOnS3, int $expTime = null)
 * @method static FilesystemAdapter getStorage()
 *
 * @package App\Services\S3
 */
class S3Facade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return S3Service::class;
    }
}