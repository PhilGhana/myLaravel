<?php
header("Content-Type:text/html; charset=utf-8");
$root = dirname(dirname(dirname(__FILE__)));
include_once ($root.'/PaymentAPI/services/PaymentAPIService.php');
try {
    $data = PaymentAPIService::buildData(
        array(
            type =>"1",
            paymentMerchantId => "DCD0430D39EC451C9370FA6B35FD355F",
            platformOrderId => "platformOrderId",
            bankCode => "",
            account => "test",
            amount => 100,
            remark => '支付寶實名',
            flagTime => time() * 1000
        )
    );
    var_dump($data);
    $url = PaymentAPIService::getCreateOrderUrl();
    var_dump($url);
    echo "<form action=\"$url/\" target=\"_blank\" method=\"post\"><input name=\"data\" value=\"$data\"/><button type=\"submit\">提交</button></form>";
} catch (Exception $e) {
    echo $e->getMessage();
}
