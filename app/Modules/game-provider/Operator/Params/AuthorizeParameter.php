<?php

namespace GameProvider\Operator\Params;

class AuthorizeParameter
{
    /**
     * 玩家id
     *
     * @var string
     */
    public $playerId;

    /**
     * 廠商編號
     *
     * @var string
     */
    public $merchantCode;

    /**
     * 金鑰
     *
     * @var string
     */
    public $token;

    /**
     * 要求序列號
     *
     * @var string
     */
    public $serialNo;
}
