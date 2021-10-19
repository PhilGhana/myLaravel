<?php

namespace GameProvider\Operator;

use GameProvider\Exceptions\CurlException;
use GameProvider\Exceptions\JSONException;

class BaseApi
{
    // const ENCRYPT_ERROR = -100;
    // const RESPONSE_ERROR = -101;
    // const UNKNOWN_ERROR = -102;

    // public $reponseCode = 0;
    protected $curlHeader = ['Content-Type:application/json;charset=utf-8'];

    protected $inputData = null;

    protected $doStuck = true;  // 卡錢flag

    /**
     * 把input拿回來
     *
     * @param array $data
     * @return void
     */
    protected function initInputData($data = null)
    {
        $method = $_SERVER["REQUEST_METHOD"] ?? 'GET';
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

        if ($data) {
            $this->inputData = $data;
        } elseif ($method === 'POST') {
            $this->inputData = strcasecmp($contentType, 'application/json') != 0
                ? ($_POST ?? [])
                : json_decode(file_get_contents('php://input'), true);
        } else {
            $this->inputData = $_GET ?: [];
        }
    }

    /**
     * 分析陣列內容回傳
     *
     * @param array $target
     * @param string $key
     * @param string $default
     * @return void
     */
    protected function arrayGet($target, $key = null, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($target)) {
                if (!array_key_exists($segment, $target)) {
                    return $default;
                }
                $target = $target[$segment];
            } elseif ($target instanceof ArrayAccess) {
                if (!isset($target[$segment])) {
                    return $default;
                }
                $target = $target[$segment];
            } elseif (is_object($target)) {
                if (!isset($target->{$segment})) {
                    return $default;
                }
                $target = $target->{$segment};
            } else {
                return $default;
            }
        }
        return $target;
    }

    /**
     * 直接取input值
     *
     * @param string $key
     * @param string $default
     * @return void
     */
    protected function input($key = null, $default = null)
    {
        if (function_exists('request') && get_class(request()) === 'Illuminate\Http\Request') {
            return request()->input($key, $default);
        } else {
            if ($this->inputData === null) {
                $this->initInputData();
            }
            return $this->arrayGet($this->inputData, $key, $default);
        }
    }

    protected function curl($content, $url, $isPost = true, $need_json = true, $need_array = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->curlHeader);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER  , false);

        if($isPost === true)
        {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
            curl_setopt($ch, CURLOPT_POST, true);
        }

        $result = curl_exec($ch);

        $reponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // 如果對方發生錯誤，直接報錯，不處理
        if($reponseCode !== 200)
        {
            // TODO : 這邊要寫到log
            throw new CurlException(get_class($this), 'curl error : ' . $reponseCode . ' ' . $url . ', ' . $content . ', ' . $result, json_encode($content));
        }

        curl_close($ch);

        if($need_json === true)
        {
            $json = json_decode($result, $need_array);

            if(!$json)
            {
                throw new JSONException(get_class($this), 'error on JSON decode ! ' . $result, $result);
            }

            return $json;
        }

        return $result;
    }

    protected function get($url, $content, $need_json = true, $need_array = false)
    {
        return $this->curl($content, $url, false, $need_json, $need_array);
    }

    protected function post($url, $content, $need_json = true, $need_array = false)
    {
        return $this->curl($content, $url, true, $need_json, $need_array);
    }

    protected function GUID()
    {
        return vsprintf('%s%s-%s-4000-8%.3s-%s%s%s0',str_split(dechex( microtime(true) * 1000 ) . bin2hex( random_bytes(8) ),4));
    }

}
