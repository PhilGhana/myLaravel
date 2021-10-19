<?php

namespace GameProvider\Exceptions;

/**
 * 轉點失敗
 */
class TransferException extends BaseException
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
