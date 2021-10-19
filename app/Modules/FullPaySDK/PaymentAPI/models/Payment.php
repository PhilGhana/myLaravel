<?php
namespace FullPay\PaymentAPI\models ;


/**
 *
 *@property string $value 平台商戶 ID (paymentMerchantId)
 *@property string $name 支付名稱
 *@property string $paymentId 第三方支付
 *@property string $paymentType 支付方式
 *@property double $maxAmount 單筆最高金額
 *@property double $minAmount 單筆最低金額
 *@property double $dayMaxAmount 當日最高限制金額
 *@property double $accountMaxAmount 當日個人最高限制金額
 *@property double $dayTotalAmount 單日累積總金額
 *@property double $accountTotalAmount 當日個人累積總金額
 *@property bool $daySurpass 當日是否超額
 *@property bool $accountSurpass 當日個人是否超額
 *@property double[] $amounts 支付面額限制
 *@property string $device => 支持裝置
 *@property string $type => 類型
 */
class Payment
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

    /**
     * 	在線支付
     */
    const TYPE_ONLINE = 'Online';

    /**
     * 	附言存款
     */
    const TYPE_REMARK = 'Remark';

    /**
     * 	QQ
     */
    const TYPE_QQ = 'QQ';

    /**
     * 	微信
     */
    const TYPE_WECHAT = 'Wechat';

    /**
     * 	支付寶
     */
    const TYPE_ALIPAY = 'Alipay';

    /**
     * 	銀聯
     */
    const TYPE_UNIONPAY = 'Unionpay';

    /**
     * 	京東
     */
    const TYPE_JD = 'JD';

    /**
     * 	支付寶轉帳(remark傳入實名)
     */
    const TYPE_ALIPAYBANK = 'AlipayBank';

    /**
     * 	支付寶轉帳(remark傳入支付寶帳號)
     */
    const TYPE_ALIPAYBANK2 = 'AlipayBank2';

    /**
     * 	GooglePay
     */
    const TYPE_GOOGLEPAY = 'GooglePay';

    /**
     * 	網路銀行
     */
    const TYPE_WEBATM = 'WebATM';

    /**
     * 	ATM
     */
    const TYPE_ATM = 'ATM';

    /**
     * 	超商代碼
     */
    const TYPE_CVS = 'CVS';

    /**
     * 	虛擬帳號
     */
    const TYPE_VIRTUAL = 'Virtual';

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
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
