<?php

namespace MultiWallet\Params;

class TransferParameter
{
    /**
     * 使用者參數
     *
     * @var MemberParameter
     */
    public $member = null;

    /**
     * 金額
     *
     * @var float
     */
    public $amount = null;

    /**
     * 記錄單號
     * 最多64字
     *
     * @var string
     */
    // public $payNO = null;

    /**
     * 序列
     *
     * @var number
     */
    public $billno;

    /**
     * 不可用額度
     *
     */
    public $fixcredit;

    /**
     * gameCategory AGTEX棋牌平台參數 參數
     *
     */
    public $gameCategory;
}
