<?php
namespace FullPay\WithdrawAPI\models ;

/**
 * @property string $orderId	訂單編號	String(30)	FullPay訂單編號
 * @property int $time	建立時間	Long(13)	產生訂單的時間
 * @property string $platformId	平台ID	String(36)
 * @property string $platformOrderId	平台訂單ID	String(50)
 * @property string $withdrawMerchantId	平台商戶ID	String(36)	平台商戶ID
 * @property string $withdrawId	第三方代付ID	String(50)	第三方代付ID
 * @property string $merchantId	商戶ID	String(50)	第三方代付商戶ID
 * @property string $withdrawType	代付方式	String(20)
 * @property string $account	會員帳號	String(50)
 * @property double $amount	代付金額	Decimal(16,5)
 * @property double $fee	手續費	Decimal(16,5)
 * @property string $tradeNo	第三方代付訂單編號	String(100)	第三方代付產生的訂單編號
 * @property string $tradeId	第三方代付訂單編號	String(100)	訂單ID(酷模第二個ID)
 * @property int $payTime	付款時間	Long(13)
 * @property int $callbackTime	回傳時間	Long(13)	第三方代付回傳時間
 * @property string $phone	會員電話	String(20)
 * @property string $idCard	身分ID	String(20)
 * @property string $bankAccount	銀行帳號	String(50)
 * @property string $bankAccountName	銀行帳號名稱	String(50)	銀行帳號申請時的姓名
 * @property string $bankBranch	銀行分行	String(50)
 * @property string $bankCode	銀行代碼	String(50)
 * @property string $bankName	銀行名稱	String(50)
 * @property string $bankProvinceCode	銀行省分代碼	String(10)
 * @property string $bankProvinceName	銀行省分名稱	String(100)
 * @property string $bankCityCode	銀行城市代碼	String(10)
 * @property string $bankCityName	銀行城市名稱	String(1000)
 * @property int $status	訂單狀態	Int(10)	詳細參考附錄
 * @property string $statusMessage	訂單狀態訊息	text
 * @property int $transactionStatus	交易狀態	Int(10)	訂單交易結果以該欄位為主，詳細參考附錄
 * @property int $paymentStatus	付款狀態	Int(10)	第三方代付回傳代碼
 * @property string $paymentStatusMessage	付款狀態訊息	String(200)	第三方代付回傳訊息
 * @property string $encryptType		加密方式	String(10)
 * @property string $sourceIp	會員IP	String(60)
 * @property string $device	裝置	String(200)
 * @property int $flagTime	(*必填)時間標記	Long(13)	以當前時間為標記，只處理3分鐘內的請求。
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
