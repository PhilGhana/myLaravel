<?php

namespace GameProvider\Operator\Multi\Config;

class BNGConfigConstract
{
    /**
     * api 網址
     *
     * @var string
     */
    public $apiUrl;

    /**
     * api token
     *
     * @var string
     */
    public $apiToken;

    /**
     * ISO-4217 的貨幣代碼
     *
     * @var string
     */
    public $currency;
    
    /**
     * 廠商名稱
     *
     * @var string
     */
    public $wl;

    /**
     * 時區 分鐘為單位
     *
     * @var int
     */
    public $tz;

    /**
     * 遊戲模式 ("REAL", "FUN") 預設 REAL
     *
     * @var string
     */
    // public $mode;

    /**
     * 是否為測試用的玩家
     *
     * @var string
     */
    // public $is_test;

    /**
     * 站點名稱
     *
     * @var string
     */
    // public $brand;

}