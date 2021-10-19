<?php

namespace App\Events;

/**
 * Undocumented class
 */
class MultiWalletStuckLog
{
    /**
     * 正常處理
     */
    const PROCESS_NORMAL = 0;

    /**
     * 卡錢處理
     */
    const PROCESS_STUCK = 1;

    /**
     * 正常狀態
     */
    const STATUS_NORMAL = 0;
    /**
     * 卡錢中
     */
    const STATUS_STUCK = 1;
    /**
     * 退還
     */
    const STATUS_ROLLBACK = 2;
    /**
     * 行為正常不處理
     */
    const STATUS_NOTHING = 3;

    /**
     * 平台代號
     *
     * @var int
     */
    public $platform_id;

    /**
     * 會員代碼
     *
     * @var int
     */
    public $member_id;

    /**
     * 卡錢項目
     * (deposit、withdraw)
     * @var string
     */
    public $type;

    /**
     * 對方主機回傳代碼
     *
     * @var string
     */
    public $error_code;

    /**
     * 對方主機回傳內容
     *
     * @var string
     */
    public $error_message;

    /**
     * 金額
     *
     * @var float
     */
    public $amount;

    /**
     * 處理狀態
     *
     * @var int
     */
    public $status = 0;

    /**
     * 是否有被阻擋
     *
     * @var int
     */
    public $process = 0;

    public function __construct ($platform_id, $member_id, $type, $error_code, $error_message, $amount, $process = 0, $status = 0)
    {
        $this->platform_id = $platform_id;
        $this->member_id = $member_id;
        $this->type = $type;
        $this->error_code = $error_code;
        $this->error_message = $error_message;
        $this->amount = $amount;
        $this->process = $process;
        $this->status = $status;
    }

}
