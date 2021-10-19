<?php
header("Content-Type:text/html; charset=utf-8");
$root = dirname(dirname(dirname(__FILE__)));
include_once ($root.'/PaymentAPI/services/PaymentAPIService.php');
try {
    echo'可使用的支付方式';
    // 取得可使用的支付方式
    $payments = PaymentAPIService::queryCanUse("1","ispr06");
    var_dump($payments);
    // 依據支付方式的銀行類型取得銀行代碼
    var_dump('支付方式-銀行代碼');
    foreach ($payments as $value) {
        echo 'Payment'.$value['name'].' '.$value['paymentType'];
        var_dump(PaymentAPIService::querySelectors($value['paymentId'],$value['paymentType']));
    }
} catch (Exception $e) {
    echo $e->getMessage();
}