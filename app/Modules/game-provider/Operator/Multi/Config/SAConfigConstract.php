<?php

namespace GameProvider\Operator\Multi\Config;

class SAConfigConstract
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
     * DES 加密金鑰
     *
     * @var string
     */
    public $secret;

    /**
     * 加密键
     *
     * @var string
     */
    public $EncrypKey;

    /**
     * MD5 键
     *
     * @var string
     */
    public $MD5Key;

    /**
     * 貨幣類型
     *
     *@var string
     */
    public $CurrencyType;

    /**
     * 用戶名
     *
     */
    // public $username;

    /**
     * 對方提供的辨識標誌
     *
     */
    public $lobbycode;

    /**
     * 遊戲網址
     *
     * @var string
     */
    public $gameUrl;

    /**
     * App加密Key
     */
    public $appEncryptKey = '';

    /**
     * 玩家後綴(for app)
     */
    public $playerSuffix = '';

    /**
     * 遊戲語言
     *
     * @var string
     */
    public $lang = '';
}
