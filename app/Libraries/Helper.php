<?php

namespace App\Libraries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 8/27/2018
 * Time: 9:23 AM
 */
class Helper
{
    /**
     * @return \Illuminate\Http\Request
     */
    public static function getRequestInstance()
    {
        return app('request');
    }

    public static function getTimeZone()
    {
        $request = self::getRequestInstance();
        $timezone = $request->get('timezone');
        return $timezone;
    }

    /**
     * Get value for the object or array with default value
     *
     * @author Binh pham
     *
     * @param object|array $object Object to get value
     * @param string $value key value
     * @param null $defaultValue default value if object's key not exist
     * @param callable $callback function callback
     *
     * @return object|array|string|null value of key in the object
     */
    public static function get($object, $value, $defaultValue = null, $callback = null)
    {
        $value = explode('.', $value);
        $dataReturn = self::getRecursive($object, $value, $defaultValue);
        if (is_callable($callback) && $dataReturn !== null && $dataReturn != '') {
            $callback($dataReturn);
        }
        return $dataReturn;
    }

    /**
     * Get value for the object or array with default value
     *
     * @author Binh pham
     *
     * @param object|array $object Object to get value
     * @param string $value key value
     * @param null $defaultValue default value if object's key not exist
     *
     * @return object|array|string|null value of key in the object
     */
    private static function getRecursive($object, $value, $defaultValue = null)
    {
        if (is_array($value)) {
            $tmpValue = $object;
            for ($i = 0, $len = count($value); $i < $len; $i++) {
                $tmpValue = self::getRecursive($tmpValue, $value[$i], $defaultValue);
            }
            return $tmpValue;
        }
        else {
            if (!isset($object)) {
                return $defaultValue;
            }
            else if (is_array($object)) {
                return isset($object[$value]) ?
                    $object[$value] : $defaultValue;
            }
            else if (is_object($object)) {
                return isset($object->$value) ?
                    $object->$value : $defaultValue;
            }
        }
    }

    public static function getItemPerPage()
    {
        $request = self::getRequestInstance();
        $itemPerPage = $request->get(config('repository.pagination.params.limit'), config('repository.pagination.limit'));
        return $itemPerPage;
    }

    /**
     * map search field
     *
     * @param Builder $query
     * @param array $fieldsSearch , format [KEY => [TABLE_NAME,FIELD,CONDITION],...],
     *                              with KEY : key using for search request,
     *                              with TABLE_NAME : table name,
     *                              with FIELD : field name,
     *                              with CONDITION : search condition, acept 2 values "=" and "LIKE", default "=",
     *
     * @return Builder
     */
    public static function searchFieldsMapping($query, array $fieldsSearch, $request = [])
    {
        $request = $request ? $request : app('request')->all();
        if (!empty($fieldsSearch)) {
            foreach ($fieldsSearch as $searchKey => $fieldSearch) {
                if (array_get($request, $searchKey, null) !== null) {
                    $searchValue = array_get($request, $searchKey);
                    $condition = null;
                    $alias = null;
                    $field = null;
                    $countSearch = count($fieldSearch);
                    if (!in_array($countSearch, [2, 3])) {
                        continue;
                    }
                    if ($countSearch == 3) {
                        $condition = $fieldSearch[2];
                        $field = $fieldSearch[1];
                        $alias = $fieldSearch[0];
                    }
                    else {
                        $condition = '=';
                        $field = $fieldSearch[1];
                        $alias = $fieldSearch[0];
                    }
                    $condition = strtoupper($condition);
                    if (!in_array(
                        strtoupper($condition),
                        ['=', 'LIKE', 'IN', '<', '>', '<=', '=>', '>=', '=<', '!=', '<>']
                    )
                    ) {
                        continue;
                    }
                    if ($condition == 'IN') {
                        $searchValue = explode(',', $searchValue);
                        $query->whereIn($alias . '.' . $field, $searchValue);
                    }
                    else {
                        if ($condition == 'LIKE') {
                            $searchValue = '%' . trim($searchValue) . '%';
                        }
                        $query->where($alias . '.' . $field, $condition, $searchValue);
                    }
                }
            }
        }
        return $query;
    }

    /**
     * map filter field
     *
     * @param Builder $query
     * @param array $fieldsSearch , format [KEY => [TABLE_NAME,FIELD],...],
     *                              with KEY : key using for search request,
     *                              with TABLE_NAME : table name
     *                              with FIELD : field name
     *
     * @return Builder
     */
    public static function sortFieldsMapping($query, array $fieldsSearch, $order = null, $orderType = null)
    {
        $request = app('request');
        if ($order == null)
            $order = $request->get('order', null);
        if (empty($order)) {
            return $query;
        }
        if ($orderType == null)
            $orderType = $request->get('sort', 'DESC');
        $orderType = strtoupper($orderType);
        if (!in_array($orderType, ['ASC', 'DESC'])) {
            $orderType = 'ASC';
        }
        $orderField = $order;
        if (!empty($fieldsSearch)) {
            foreach ($fieldsSearch as $searchKey => $fieldSearch) {
                if ($searchKey == $orderField) {

                    $alias = null;
                    $field = null;
                    $countSearch = count($fieldSearch);
                    if ($countSearch != 2) {
                        continue;
                    }
                    $field = $fieldSearch[1];
                    $alias = $fieldSearch[0];
                    if (!empty($alias)) {
                        $query->orderBy($alias . '.' . $field, $orderType);
                    }
                    else {
                        $query->orderBy($field, $orderType);
                    }
                    break;
                }
            }
        }
        return $query;
    }

    /**
     * @param $query
     * @param $search
     * @param $fieldSearch
     *
     * @return mixed
     */
    static function addConditionToQuery($query, $search, $fieldSearch)
    {
        if (!$search) {
            return $query;
        }
        foreach ($search as $key => $val) {
            if (!isset($fieldSearch[$key])) {
                continue;
            }
            $field = $fieldSearch[$key]['field'];
            switch ($fieldSearch[$key]['type']) {
                case 'string':
                    $compare = $fieldSearch[$key]['compare'] ?? 'like';
                    if (strtolower(trim($compare)) == 'like') {
                        $val = '%' . $val . '%';
                    }
                    $query->where(DB::raw($fieldSearch[$key]['field']), $compare, $val);
                    break;
                case 'date':
                    $val = str_replace('"', '', $val);
                    $dateFormat = date('Y-m-d', strtotime($val));
                    $query->where(
                        DB::raw('DATE(' . $field . ')'),
                        $fieldSearch[$key]['compare'],
                        $dateFormat
                    );
                    break;
                case 'array':
                    $delimiter = $fieldSearch[$key]['delimiter'] ?? ",";
                    $val = explode($delimiter, $val);
                    $query->whereIn($field, $val);
                    break;
                default:
                    $query->where($fieldSearch[$key]['field'], $fieldSearch[$key]['compare'], $val);
                    break;
            }
        }
        return $query;
    }

    /**
     * Get real IP from client
     *
     * @return string
     */
    public static function getRealClientIP()
    {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_VIA',
            'HTTP_X_COMING_FROM',
            'HTTP_COMING_FROM',
            'HTTP_CLIENT_IP',
        ];
        foreach ($headers as $header) {
            if (isset ($_SERVER [$header])) {
                //Check server
                if (($pos = strpos($_SERVER [$header], ',')) != false) {
                    $ip = substr($_SERVER [$header], 0, $pos);//True
                }
                else {
                    $ip = $_SERVER [$header]; //False
                }
                $ipnumber = ip2long($ip);
                if ($ipnumber !== -1 && $ipnumber !== false && (long2ip($ipnumber) === $ip)) {
                    if (($ipnumber - 184549375)
                        && // Not in 10.0.0.0/8
                        ($ipnumber - 1407188993)
                        && // Not in 172.16.0.0/12
                        ($ipnumber - 1062666241)
                    ) // Not in 192.168.0.0/16
                    {
                        if (($pos = strpos($_SERVER [$header], ',')) != false) {
                            $ip = substr($_SERVER [$header], 0, $pos);
                        }
                        else {
                            $ip = $_SERVER [$header];
                        }
                    }
                    return $ip;
                }
            }
        }
        return $_SERVER ['REMOTE_ADDR'];
    }

    public static function runningInConsole(): bool
    {
        return app()->runningInConsole();
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public static function createRestful()
    {
        return app(IOC_RESTFUL);
    }

    /**
     * @param string $method
     * @param string $url
     *
     * @return \GuzzleHttp\Psr7\Request
     */
    public static function createRestfulRequest($method, $url)
    {
        return app()->make(
            IOC_RESTFUL_REQUEST,
            [
                $method,
                $url,
            ]
        );
    }

    public static function trimArray($input)
    {
        return array_map('trim', $input);
    }

    public static function isAbsolutePath($file)
    {
        return strspn($file, '/\\', 0, 1)
            || (strlen($file) > 3 && ctype_alpha($file[0])
                && substr($file, 1, 1) === ':'
                && strspn($file, '/\\', 2, 1)
            )
            || null !== parse_url($file, PHP_URL_SCHEME);
    }

    public static function makePath($path)
    {
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        if (is_dir($dir)) {
            return true;
        }
        else {
            if (self::makePath($dir)) {
                if (mkdir($dir)) {
                    chmod($dir, 0777);
                    return true;
                }
            }
        }
        return false;
    }

    public static function replaceExtensionPath($filename, $new_extension)
    {
        $info = pathinfo($filename);
        return ($info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '')
            . $info['filename']
            . '.'
            . $new_extension;
    }

    public static function appendQueryStringToUrl($url, array $queries)
    {
        $stringQueries = http_build_query($queries);
        $parsedUrl = parse_url($url);
        if ($parsedUrl['path'] == null) {
            $url .= '/';
        }
        @$separator = ($parsedUrl['query'] == NULL) ? '?' : '&';
        $url .= $separator . $stringQueries;
        return $url;
    }
}