<?php

namespace GameProvider\Operator\Params;

use GameProvider\Operator\Params\SyncCallBackParameter;

class TransferParameter
{
    /**
     * 使用者參數
     *
     * @var \GameProvider\Operator\Params\MemberParameter
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
     * 以下為單一錢包用
     */

     /**
     * 把點數轉給我們，一般用在派彩
     */
    const TYPE_PAY_IN = 'type_pay_in';

    /**
     * 取消下注，所以點數還我們，算是payin的一種
     */
    const TYPE_PAY_IN_CANCEL = 'type_pay_in_cancel';

    /**
     * 把點數轉出去，一般用在投注時
     */
    const TYPE_PAY_OUT = 'type_pay_out';

    /**
     * 對方由於紅包、紅利等獎勵，把點數轉來
     */
    const TYPE_PAY_PRIZE = 'type_pay_prize';

    /**
     * 單一錢包用，使用上述常數確認轉點方式
     *
     * @var string
     */
    public $type = null;

    /**
     * 廠商編號
     *
     * @var string
     */
    public $merchantCode;

    /**
     * 要求序列號
     *
     * @var string
     */
    public $serialNo;

    /**
     * 需要同步時使用的參數
     *
     * @var SyncCallBackParameter
     */
    public $syncCallBackParam = null;

    /**
     * 關聯流水號
     *
     * @var string
     */
    public $referenceId = null;

    /**
     * AG 參數
     * cagent+序列
     *
     */
    public $bullno = null;
    /**
     * AG 參數
     * 不可用額度
     *
     */
    public $fixcredit = null;

    /**
     * AG 參數, AGTEX 平台才用到
     * 默認=0, 1=對戰, 0=對賭
     *
     */
    public $gameCategory = null;
}
