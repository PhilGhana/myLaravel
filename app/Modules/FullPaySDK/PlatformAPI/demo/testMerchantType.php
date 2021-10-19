<?php
header("Content-Type:text/html; charset=utf-8");
$root = dirname(dirname(dirname(__FILE__)));
include_once ($root.'/PlatformAPI/services/PlatformAPIService.php');
try {
    var_dump('新增商戶類型',PlatformAPIService::addMerchantType("1", 'WOW黑金', 'test'));
} catch (Exception $e) {
    var_dump($e->getMessage());
}
try {
    var_dump('修改商戶類型',PlatformAPIService::modifyMerchantType("1", 'WOW黑金', time()));
    
} catch (Exception $e) {
    var_dump($e->getMessage());
}
try {
    var_dump('查詢商戶類型',PlatformAPIService::queryMerchantType());
} catch (Exception $e) {
    var_dump($e->getMessage());
}