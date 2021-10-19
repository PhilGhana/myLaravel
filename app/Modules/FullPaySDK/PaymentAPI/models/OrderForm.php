<?php
namespace FullPay\PaymentAPI\models ;


/**
 *
 *@property string type 自訂商戶 id (Fullpay 平台上的「自訂商戶類型」 ID)
 *@property string paymentMerchantId 支付商戶 ID - Payment.value (paymentMerchantId)
 *@property string platformOrderId 訂單編號
 *@property string bankCode 銀行代碼 (由 querySelectors 取得, 若無資料則非必填)
 *@property string account 會員帳號
 *@property double amount 額度
 *@property double remark 備註
 *@property double flagTime 交易時間, 自動產生 (毫秒)
 */
/**
 * Undocumented class
 */
class OrderForm
{

    protected $data;

    public function __construct()
    {
        $this->data = [];
        $this->data['flagTime'] = time() * 1000;
    }

    public function __get ($key)
    {
        return $this->data[$key] ?? null;
    }

    public function __set ($key, $value)
    {
        return $this->data[$key] = $value;
    }

    public function __isset ($key)
    {
        return isset($this->data[$key]);
    }

    public function toArray()
    {
        return $this->data;
    }

}
