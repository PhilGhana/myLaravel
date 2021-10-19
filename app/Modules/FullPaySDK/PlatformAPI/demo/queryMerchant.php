<?php
header("Content-Type:text/html; charset=utf-8");
$root = dirname(dirname(dirname(__FILE__)));
include_once ($root.'/PlatformAPI/services/PlatformAPIService.php');

try {
    echo 'queryPaymentMerchant' ;
    var_dump('查詢支付商戶',PlatformAPIService::queryPaymentMerchant());
} catch (Exception $e) {
    var_dump($e->getMessage());
}
try {
    echo 'queryWithdrawMerchant' ;
    var_dump('查詢代付商戶',PlatformAPIService::queryWithdrawMerchant());
} catch (Exception $e) {
    var_dump($e->getMessage());
}