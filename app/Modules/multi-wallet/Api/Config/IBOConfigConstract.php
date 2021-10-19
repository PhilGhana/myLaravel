<?php

namespace MultiWallet\Api\Config;

class IBOConfigConstract
{
    /**
     * api 網址
     *
     * @var string
     */
    public $apiUrl;

    /**
     * 代理商ID
     *
     * @var string
     */
    public $agid;

    /**
     * 代理商帳號
     *
     * @var string
     */
    public $username;

    /**
     * 代理商密碼
     *
     * @var string
     */
    public $password;

    /**
     * AES 加密金鑰
     *
     * @var string
     */
    public $secret;

    /**
     * 幣別
     *
     * @var string
     */
    public $currency;

    /**
     * 簡易代碼
     *
     * @var string
     */
    public $agentCode;

    /**
     * 代理商名稱
     *
     * @var string
     */
    public $agname;
}
