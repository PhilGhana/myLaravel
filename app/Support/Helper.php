<?php

use Illuminate\Support\Str;
use Hashids\Hashids;

if (!function_exists('redis')) {

    /**
     * 取得 redis
     *
     * @param string $connect 連線名稱 (預設 default)
     * @return Redis
     */
    function redis($connect = 'default')
    {
        return app('redis')->connection($connect);
    }
}

if (!function_exists('apiResponse')) {

    /**
     * 重寫 response
     *
     * @return \App\Providers\ApiResponseServiceProvider
     */
    function apiResponse()
    {
        return app('apiResponse');
    }
}

if (!function_exists('user')) {
    /**
     * 登入使用者物件, 用來管理登入狀態 & 登入者相關資訊
     *
     * @return \App\Providers\UserServiceProvider
     */
    function user()
    {
        return app('user');
    }
}

if (!function_exists('sconfig')) {
    /**
     * 平台系統變數
     *
     * @return \App\Services\Redis\SystemConfigCacheService
     */
    function sconfig()
    {
        return app('sconfig');
    }
}

if (!function_exists('fconfig')) {
    /**
     * 加盟商系統變數
     *
     * @return \App\Services\Redis\FranchiseeConfigCacheService
     */
    function fconfig()
    {
        return app('fconfig');
    }
}

if (!function_exists('sys')) {

    /**
     * 建立 系統參數(SystemConfig) 的快取物件
     *
     * @return \App\Services\System\SystemConfigCacheService
     */
    function sys()
    {
        return app(\App\Services\System\SystemConfigCacheService::class);
    }
}

if (!function_exists('ipMark')) {

    function ipMark($ip)
    {

        $fullIP = user()->model()->role->full_ip ?? 0;
        $markip = $ip;

        if (!$fullIP && filter_var($markip, FILTER_VALIDATE_IP)) {
            $ipv6 = filter_var($markip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
            $ips = $ipv6 ? explode(':', $markip) : explode('.', $markip);
            $first = $ips[0];
            $last = end($ips);
            $markip = $ipv6
                ? "{$first}:*:*:*:*:*:*:{$last}"
                : "{$first}.*.*.{$last}";
        }
        return $markip;
    }
}


if (!function_exists('infoMark')) {
    function infoMark($value)
    {
        if (!$value || user()->model()->role->full_info) {
            return $value;
        } else {
            $len = mb_strlen($value);
            $numDisplays = min($len, 4);
            $numHides = $len - $numDisplays;
            return '******' . mb_substr($value, -$numDisplays);
        }
    }
}

if (!function_exists('localeDatetime')) {

    /**
     * 取得 local 時區的時間
     *
     * @param string $formatString
     * @return void
     */
    function localeDatetime(string $formatString)
    {
        $time = new DateTime($formatString);
        $time->setTimezone(new DateTimeZone(config('app.timezone')));
        return $time;
    }
}

if (!function_exists('classConst')) {

    /**
     * 取得 class 物件的常數
     *
     * @param mixed $obj
     * @param string $constName 常數名稱
     * @return void
     */
    function classConst($obj, string $constName)
    {
        $ref = new ReflectionClass(get_class($obj));
        return $ref->getConstant($constName);
    }
}

if (!function_exists('uniqueId')) {
    function uniqueId(string $input = null)
    {
        if (!$input) {
            $input = Str::uuid()->toString();
        }
        return (new Hashids($input))->encode(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
    }
}
