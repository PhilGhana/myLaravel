<?php

namespace GameProvider\Operator\Multi\Config;

class PNGConfigConstract
{
    /**
     * soap 網址
     *
     * @var string
     */
    public $wsdlUrl;

    /**
     * api 網址
     *
     * @var string
     */
    public $apiUrl;

    /**
     * soap 帳號
     *
     * @var string
     */
    public $username;

    /**
     * soap 密碼
     *
     * @var string
     */
    public $password;

    /**
     * 使用幣種
     *
     * @var string
     */
    public $currency;

    /**
     * 語言
     *
     * @var string
     */
    public $language;

    /**
     * 廠商編碼
     *
     * @var string
     */
    public $brandId;

    /**
     * 國家
     *
     * @var string
     */
    public $country;

    /**
     * 遊戲網址
     *
     * @var string
     */
    public $gameUrl;

    /**
     * Product group
     *
     * @var integer
     */
    public $pid;

    public $allowLiveFeedIps = [];

}
