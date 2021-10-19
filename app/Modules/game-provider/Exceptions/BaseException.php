<?php

namespace GameProvider\Exceptions;

use Exception;
/**
 * 基礎繼承用
 */
class BaseException extends Exception
{
    /**
     * 平台名稱
     */
    public $platform = '';
    /**
     * 錯誤內容
     */
    public $content = '';

    public function __construct($platform, $message, $content = '')
    {
        // 存資料庫
        $class_name = get_class($this);

        $this->platform = $platform;
        $this->content  = $content;

        parent::__construct($message);
    }
}
