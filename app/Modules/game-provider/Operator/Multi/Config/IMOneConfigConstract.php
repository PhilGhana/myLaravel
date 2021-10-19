<?php

namespace GameProvider\Operator\Multi\Config;

class IMOneConfigConstract
{
    /**
     * api 網址
     *
     * @var string
     */
    public $apiUrl;

    /**
     * 營運商代瑪
     *
     * @var string
     */
    public $merchantCode;

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
}
