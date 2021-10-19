<?php
header("Content-Type:text/html; charset=utf-8");
$root = dirname(dirname(dirname(__FILE__)));
include_once ($root.'/PaymentAPI/services/PaymentAPIService.php');
try {
    echo'可使用的支付方式';
    // 取得可使用的支付方式
    $platforms = PaymentAPIService::queryPlatforms();
    var_dump($platforms);
} catch (Exception $e) {
    echo $e->getMessage();
}