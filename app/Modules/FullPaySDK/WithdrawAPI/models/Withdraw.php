<?php
namespace FullPay\WithdrawAPI\models ;


/**
 *
 * @property string $value 平台商戶ID	平台自行在FullPay後台新增的平台商戶ID
 * @property string $name 支付名稱	平台自行定義的支付名稱
 * @property string $type 類型	詳見附錄
 * @property int $device 支持裝置	0:通用、1:PC、2:移動裝置
 * @property string $withdrawId 第三方支付
 * @property string $withdrawType 支付方式
 * @property double $minAmount 單筆最低金額
 * @property double $maxAmount 單筆最高金額
 * @property double $dayMaxAmount 當日最高限制金額
 * @property double $dayTotalAmount 當日累積總金額	目前都會回傳0，不外流~
 * @property double $accountMaxAmount 當日個人最高限制金額
 * @property double $accountTotalAmount 當日個人累積總金額	目前都會回傳0，不外流~
 * @property boolean $daySurpass 當日是否超額	true/false
 * @property boolean $accountSurpass 當日個人是否超額	true/false
 */
class Withdraw
{
    /**
     * 通用
     */
    const DEVICE_UNIVERSAL = 0;
    /**
     * PC
     */
    const DEVICE_PC = 1;
    /**
     * 攜帶裝置
     */
    const DEVICE_MOBILE = 2;

    protected $data;

    public function __construct($data)
    {
        $this->device = intval($data['device'] ?? 0);
        $this->data = $data;
        $this->maxAmount = doubleval($data['maxAmount'] ?? 0);
        $this->dayMaxAmount = doubleval($data['dayMaxAmount'] ?? 0);
        $this->dayTotalAmount = doubleval($data['dayTotalAmount'] ?? 0);
        $this->accountMaxAmount = doubleval($data['accountMaxAmount'] ?? 0);
        $this->accountTotalAmount = doubleval($data['accountTotalAmount'] ?? 0);
        $this->daySurpass = boolval($data['daySurpass'] ?? false);
        $this->accountSurpass = boolval($data['accountSurpass'] ?? false);
    }

    public function __get ($key)
    {
        return $this->data[$key] ?? null;
    }

    public function __isset ($key)
    {
        return isset($this->data[$key]);
    }

}
