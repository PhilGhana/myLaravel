<?php

namespace MultiWallet\Api;

class BaseApi
{
    const ENCRYPT_ERROR = -100;
    const RESPONSE_ERROR = -101;
    const UNKNOWN_ERROR = -102;

    public $reponseCode = 0;
    protected $curlHeader = ['Content-Type:application/json;charset=utf-8'];

    protected function curl($content, $url, $isPost = true, $need_json = true, $need_array = false)
    {
        // $this->curlHeader[] = 'Content-Length: ' . strlen($content);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->curlHeader);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER  , false);

        if($isPost === true)
        {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        $result = curl_exec($ch);

        $this->reponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if($need_json === true)
        {
            return json_decode($result, $need_array);
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

}
