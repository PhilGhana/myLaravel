<?php
header("Content-Type:text/html; charset=utf-8");
$root = dirname(dirname(dirname(__FILE__)));
include_once ($root.'/WithdrawAPI/services/WithdrawAPIService.php');
try {
    $data = WithdrawAPIService::createOrder(array(
        type =>"1",
        withdrawMerchantId=>"B59E84748E7647A8841EE00B936C95DD",//代付商戶ID
        platformOrderId=>"testOrderId", // 平台訂單ID
        phone=>"123456789",//電話號碼
        idCard=>"idCard",// 會員身分ID
        account=>"test",// 會員遊戲帳號
        amount=>"100",//金額
        bankAccount=>"testbankaccount",//銀行帳號
        bankAccountName=>"test",//銀行帳戶姓名
        bankBranchCode=>"test",//銀行分行行號
        bankBranchName=>"test",//銀行分行名稱
        bankCode=>"1",//銀行編號
        bankName=>"中国工商银行",//銀行名稱
        bankProvinceCode=>"11",//銀行省代碼
        bankProvinceName=>"北京",//銀行省名稱
        bankCityCode=>"110000",//銀行城市代碼
        bankCityName=>"北京",//銀行城市名稱
        flagTime=>time() * 1000
    ));
    var_dump($data);
    // $url = WithdrawAPIService::getCreateOrderUrl();
    // var_dump($url);
    // echo "<form action=\"$url/\" target=\"_blank\" method=\"post\"><input name=\"data\" value=\"$data\"/><button type=\"submit\">提交</button></form>";
} catch (Exception $e) {
    echo $e->getMessage();
}
