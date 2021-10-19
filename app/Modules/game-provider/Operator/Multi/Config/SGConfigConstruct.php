<?php

namespace GameProvider\Operator\Multi\Config;

class SGConfigConstruct
{
    /**
     * api 網址
     *
     * @var string
     */
    public $apiUrl;

    /**
     *  station.
     *
     * @var string
     */
    public $station = '';
    /**
     * 介接層級(設定N，必須從N+1開始建立組織).
     */
    public $rootLevel = 0;

    public $is_t1 = false;
}
