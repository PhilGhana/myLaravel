<?php

namespace GameProvider\Exceptions;

/**
 * 取餘額錯誤
 */
class BalanceException extends BaseException
{
    /**
     * 是否進行卡錢
     */
    public $doStuck = true;

    public function __construct($plaform, $message, $content = '', $doStuck = true)
    {
        $this->doStuck = $doStuck;

        parent::__construct($plaform, $message, $content);
    }

}
