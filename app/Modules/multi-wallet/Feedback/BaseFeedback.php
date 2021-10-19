<?php

namespace MultiWallet\Feedback;

class BaseFeedback
{
    /**
     * 錯誤代碼
     *
     * @var string
     */
    public $error_code = null;

    /**
     * 錯誤訊息
     *
     * @var string
     */
    public $error_msg = '';

    /**
     * 對方主機錯誤代碼
     *
     * @var integer
     */
    public $response_code = 0;
}
