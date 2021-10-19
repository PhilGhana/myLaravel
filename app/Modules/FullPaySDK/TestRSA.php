<?
header("Content-Type:text/html; charset=utf-8");
include_once ('./PaymentAPI/services/PaymentAPIService.php');
try {
    $encryptData = $_POST['data'];
    if (! isset($encryptData)) {
        $encryptData = $_GET['data'];
    }
    echo PaymentAPIService::decrypt($encryptData);
} catch (Exception $e) {
    echo $e->getMessage();
}

