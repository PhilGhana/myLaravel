<?php

namespace GameProvider\Operator\Params;

class SyncCallBackParameter
{
    /**
     * 注單編號
     *
     * @var string
     */
    public $mid = null;

    /**
     * 遊戲代碼
     *
     * @var string
     */
    public $gameCode = null;

    /**
     * 使用者名稱
     *
     * @var string
     */
    public $username = null;

    /**
     * 投注時間
     *
     * @var string
     */
    public $betAt = null;

    /**
     * 報表查詢時間
     *
     * @var string
     */
    public $reportAt = null;

    /**
     * 桌號
     *
     * @var string
     */
    public $table = null;

    /**
     * 局號
     *
     * @var string
     */
    public $round = null;

    /**
     * 投注內容
     *
     * @var string
     */
    public $content = null;

    /**
     * 會員退水
     *
     * @var float
     */
    public $waterAmount = 0;

    /**
     * 投注金額
     *
     * @var float
     */
    public $betAmount = 0;

    /**
     * 有效注額
     *
     * @var float
     */
    public $validAmount = 0;

    /**
     * 中獎金額
     *
     * @var float
     */
    public $winAmount = 0;

    /**
     * 彩金
     *
     * @var float
     */
    public $prize = 0;

    /**
     * 小費
     *
     * @var float
     */
    public $tip = 0;

    /**
     * IP
     *
     * @var string
     */
    public $ip = '';

    /**
     * 注單狀態
     *
     * @var string
     */
    public $status = null;

    /**
     * 轉帳流水號
     *
     * @var string
     */
    public $uid = null;

    /**
     * 關聯的流水號
     *
     * @var string
     */
    public $referenceId = null;

    /**
     * 開獎時間
     *
     * @var string 
     */
    public $settleAt = null;

    /**
     * 賽事時間
     *
     * @var string
     */
    public $gameAt = null;
}
