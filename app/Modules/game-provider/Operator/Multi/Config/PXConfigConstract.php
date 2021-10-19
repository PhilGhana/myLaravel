<?php

namespace GameProvider\Operator\Multi\Config;

class PXConfigConstract
{
    /**
     * api 網址
     *
     * @var string
     */
    public $apiUrl;

    /**
     * AES 加密金鑰
     *
     * @var string
     */
    public $appkey;

    /**
     * 代理商帳號
     *
     * @var string
     */
    public $account;

    /**
     * 代理商密碼
     *
     * @var string
     */
    public $password;

    /**
     * 代理商 IP
     *
     * @var string
     */
    public $ip;

    /**
     * 代理商所属子渠道 ID
     *
     * @var integer
     */
    public $subplatid = 0;

    /**
     * 請求進入的遊戲id
     *
     * @var int
     */
    public $gameId;

    /**
     * 平台號碼
     *
     * @var integer
     */
    public $platid = 0;

    /**
     * 貨幣
     *
     * @var string
     */
    public $currency = '';

    /**
     * 語系 
     *
     * @var string
     */
    public $lang = '';
}
