<?php
namespace FullPay\WithdrawAPI\models ;

/**
 *
 * @property string $type	(*必填)自訂商戶類型	string(30)	平台自訂類型
 * @property string $withdrawMerchantId	(*必填)平台商戶ID	string(36)	透過FullPay後台自行新增代付方式的平台商戶ID
 * @property string $platformOrderId	平台訂單ID	string(50)	平台可以自行產生訂單號，用來辨識訂單
 * @property string $phone	會員手機號碼	string(50)	會員手機號碼
 * @property string $idCard	會員身分ID	string(50)	會員身分ID
 * @property string $account	(*必填)會員帳號	string(50)	以便平台辨識訂單為哪位會員的
 * @property double $amount	(*必填)金額	decimal(16,5)	代付金額
 * @property string $bankAccount	(*必填)會員銀行帳號	string(50)	會員銀行帳號
 * @property string $bankAccountName	(*必填)銀行帳號姓名	string(50)	銀行帳號登記的姓名
 * @property string $bankBranchCode	銀行分行代號	string(50)	銀行分行代號
 * @property string $bankBranchName	(*必填)銀行分行名稱	string(200)	銀行分行名稱
 * @property string $bankCode	銀行代碼	string(50)	如果代付方式類型為 BANK，則必須帶入銀行代碼(不同代付可能不一樣，代碼必須為FullPay提供的)。
 * @property string $bankName	(*必填)銀行分行名稱	string(50)	銀行分行名稱
 * @property string $bankProvinceCode	銀行省代碼	string(10)	銀行省代碼
 * @property string $bankProvinceName	銀行省名稱	string(100)	銀行省名稱
 * @property string $bankCityCode	銀行城市代碼	string(10)	銀行城市代碼
 * @property string $bankCityName	銀行城市名稱	string(100)	銀行城市名稱
 * @property int $flagTime	(*必填<)時間標記	long(13)	以當前時間為標記，只處理3分鐘內的訂單請求
 *
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
