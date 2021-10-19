<?php

namespace GameProvider\Operator\Feedback;

class TransferFeedback
{
    /**
     * 對方注單編號
     *
     * @var string
     */
    public $remote_payno = null;

    /**
     * 儲存後的餘額 null為未回傳.
     *
     * @var float
     */
    public $balance = null;

    /**
     * 對方伺服器回應代碼
     *
     * @var int
     */
    public $response_code = 0;
}
