<?php

namespace GameProvider\Operator\Multi\Config;

class DGConfigConstruct
{
    /**
     * api 網址
     *
     * @var string
     */
    public $apiUrl;

    /**
     * 代理帳號
     *
     * @var string
     */
    public $agentName;

    /**
     * APIkey
     *
     * @var string
     */
    public $APIkey;

    /**
     * MD5(agentName+API key + 随机字符串)
     *
     * @var string
     */
    public $token;

    /**
     * 生成token的随机字符串
     *
     * @var string
     */
    public $random;

    /**
     * 目标限红组
     *
     * @var string
     */
    public $data;

    /**
     * 会员币种
     *
     * @var string
     */
    public $currency;

    /**
     * 会员盈利限制[仅统计当天下注]
     *
     * @var string
     */
    public $winLimit;

    /**
     * 会员客户端语言
     * 0    en    英文
     * 1    cn    中文简体
     * 2    tw    中文繁体
     * 3    kr    韩语
     * 4    my    缅甸语
     * 5    th    泰语
     * 6    vi    越南语
     *
     * @var string
     */
    public $lang;

}