<?php

namespace GameProvider\Operator\Multi\Config;

class IFUNConfigConstract
{
    /**
     * api 網址
     *
     * @var string
     */
    public $apiUrl;

    /**
     * 加密金鑰
     *
     * @var string
     */
    public $secret;

    /**
     * partner_id
     *
     * @var string
     */
    public $partner_id;

    /**
     * currency
     *
     * MYR TWD RMB THB JPY USD
     * 
     * @var string
     */
    public $currency;

    /**
     * 語言
     * en cn tw jp
     *
     * @var string
     */
    public $lang;
}
