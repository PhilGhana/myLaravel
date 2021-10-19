<?php

namespace MultiWallet\Feedback;

class TransferFeedback extends BaseFeedback
{
    /**
     * 對方注單編號
     *
     * @var string
     */
    public $remote_payno = null;

    /**
     * 儲存後的餘額
     *
     * @var float
     */
    public $balance = 0;

    /**
     * 對方伺服器回應代碼
     *
     * @var integer
     */
    public $response_code = 0;
}
