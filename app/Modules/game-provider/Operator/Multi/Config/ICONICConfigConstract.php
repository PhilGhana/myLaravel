<?php

namespace GameProvider\Operator\Multi\Config;

class ICONICConfigConstract
{
    /**
     * api 網址
     *
     * @var string
     */
    public $apiUrl;

    /**
     * 帳號
     *
     * @var string
     */
    public $username;

    /**
     * 密碼
     *
     * @var string
     */
    public $password;

    /**
     * 貨幣代碼
     * 接受的代碼：CNY, USD, EUR, JPY, MYR, IDR, VND, THB, KRW, CGC
     *
     * @var string
     */
    public $currency;

    /**
     * 遊戲預設語言
     *
     * @var string
     */
    public $language;

    /**
     * 平台代號
     *
     * @var string
     */
    public $platformId;

    /**
     * 遊戲連結
     *
     * @var string
     */
    public $gameUrl;
}
