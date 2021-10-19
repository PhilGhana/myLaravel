<?php
namespace FullPay\PaymentAPI\models ;

/**
 *
 * @property string $orderId	訂單編號	String(30)	FullPay訂單編號
 * @property int $time	建立時間	Long(13)	產生訂單的時間
 * @property string $platformId	平台ID	String(36)
 * @property string $paymentMerchantId	平台商戶ID	String(36)	平台商戶ID
 * @property string $platformOrderId	平台訂單ID	String(50)	平台訂單ID
 * @property string $paymentId	第三方支付ID	String(50)	第三方支付ID
 * @property string $merchantId	商戶ID	String(50)	第三方支付商戶ID
 * @property string $paymentType	支付方式	String(20)
 * @property string $bankCode	銀行代碼	String(50)
 * @property string $account	會員帳號	String(50)
 * @property double $amount	支付金額	Decimal(16,5)
 * @property double $fee	手續費	Decimal(16,5)
 * @property string $tradeNo	第三方支付訂單編號	String(100)	第三方支付產生的訂單編號
 * @property int $payTime	付款時間	Long(13)
 * @property int $callbackTime	回傳時間	Long(13)	第三方支付回傳時間
 * @property int $status	訂單狀態	Int(10)	詳細參考附錄
 * @property string $statusMessage	訂單狀態訊息	text
 * @property int $transactionStatus	交易狀態	Int(10)	訂單交易結果以該欄位為主，詳細參考附錄
 * @property int $paymentStatus	付款狀態	Int(10)	第三方支付回傳代碼
 * @property string $paymentStatusMessage	付款狀態訊息	String(200)	第三方支付回傳訊息
 * @property string $encryptType		加密方式	String(10)
 * @property string $sourceIp	會員IP	String(60)
 * @property string $device	裝置	String(200)
 * @property int $flagTime	(*必填)時間標記	Long(13)
 */
/**
 * Undocumented class
 */
class Order
{

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function __get ($key)
    {
        return $this->data[$key] ?? null;
    }

    public function toArray()
    {
        return $this->data;
    }

}
