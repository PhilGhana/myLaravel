<?php
header("Content-Type:text/html; charset=utf-8");
$root = dirname(dirname(dirname(__FILE__)));
include_once ($root.'/WithdrawAPI/services/WithdrawAPIService.php');
try {
    echo'可使用的代付方式';
    // 取得可使用的代付方式
    $platforms = WithdrawAPIService::queryPlatforms();
    var_dump($platforms);
} catch (Exception $e) {
    echo $e->getMessage();
}