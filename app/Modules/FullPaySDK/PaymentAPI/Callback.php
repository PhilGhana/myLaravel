<?
header("Content-Type:text/html; charset=utf-8");
include_once ('./services/PaymentAPIService.php');
try {
    $order = PaymentAPIService::getCallbackOrder();
    var_dump($order);
    //實作取得訂單後處理
} catch (Exception $e) {
    echo $e->getMessage();
}

