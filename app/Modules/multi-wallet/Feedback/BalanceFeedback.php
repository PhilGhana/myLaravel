<?php

namespace MultiWallet\Feedback;

class BalanceFeedback extends BaseFeedback
{
    /**
     * 餘額
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
