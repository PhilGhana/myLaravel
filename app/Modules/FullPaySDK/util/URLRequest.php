<?php
namespace FullPay\util;

use Exception;

/**
 *
 * @author Clare
 *
 */
abstract class URLRequest
{

    /**
     * 發出post request
     *
     * @param string $url
     * @param array $params
     * @return string
     */
    public static function post($url, $params=array())
    {
        $ch = self::defaultCHSet();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $output = curl_exec($ch);
        self::checkeException($ch, $output);
        curl_close($ch);
        return (string) $output;
    }

    /**
     * 送出get request
     *
     * @param string $url
     * @param array $params
     * @return string
     */
    public static function get($url, $params=array())
    {
        $ch = self::defaultCHSet();
        $query = http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);
        $output = curl_exec($ch);
        self::checkeException($ch, $output);
        curl_close($ch);
        return (string) $output;
    }

    /**
     * 檢查是否請求成功
     *
     * @param
     *            curl http $ch
     * @throws Exception
     */
    static function checkeException($ch, $output)
    {
        if (curl_errno($ch)) {
            throw new Exception(__FUNCTION__ . ' Curl error: ' . curl_error($ch));
        } else {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code > 300) {
                throw new Exception(__FUNCTION__ . ' HTTP code: ' . $http_code . ' Response: ' . $output);
            }
        }
    }

    /**
     * 初始化
     *
     * @return unknown
     */
    static function defaultCHSet()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        return $ch;
    }
}