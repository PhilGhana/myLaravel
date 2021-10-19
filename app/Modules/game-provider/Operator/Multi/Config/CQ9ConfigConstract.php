<?php

namespace GameProvider\Operator\Multi\Config;

class CQ9ConfigConstract
{
    /**
     * api 網址
     *
     * @var string
     */
    public $apiUrl;

    /**
     * 系統商 Token
     *
     * @var string
     */
    public $token;

    /**
     * 遊戲預設語言
     *
     * @var string
     */
    public $language;

    /**
     * 是否是透過app 執行遊戲，Y=是，N=否，預設為N
     *
     * @var string
     */
    public $app;

    /**
     * 是否開啟阻擋不合遊戲規格瀏覽器提示， Y=是，N=否，預設為N
     *
     * @var string
     */
    public $detect;
}