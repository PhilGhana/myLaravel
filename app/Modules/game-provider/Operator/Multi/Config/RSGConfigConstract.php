<?php

namespace GameProvider\Operator\Multi\Config;

class RSGConfigConstract
{
    /**
     * api 網址
     *
     * @var string
     */
    public $apiUrl;

    /**
     * des-cbc Key
     *
     * @var string
     */
    public $DesKey;

    /**
     * des-ib Key
     *
     * @var string
     */
    public $DesIv;

    /**
     * 系統代碼(只限英數)
     *
     * @var string
     */
    public $systemCode;

    /**
     * 站台代碼(只限英數)
     *
     * @var string
     */
    public $webId;

    /**
     * 幣別代碼(請參照附件)
     *
     * @var string
     */
    public $currency;

    /**
     * 系統提供的Client ID
     *
     * @var string
     */
    public $clientID;

    /**
     * 系統提供的Client ID
     *
     * @var string
     */
    public $clientSecret;

    public $timestamp;

    public $lang;

}
