<?php
header("Content-Type:text/html; charset=utf-8");
$root = dirname(dirname(dirname(__FILE__)));
include_once ($root.'/WithdrawAPI/services/WithdrawAPIService.php');
try {
    echo'可使用的代付方式';
    // 取得可使用的代付方式
    $payments = WithdrawAPIService::queryCanUse("1","ispr06");
    var_dump($payments);
    // 依據代付方式的銀行類型取得下拉資料
    var_dump('代付方式-下拉資料');
    foreach ($payments as $value) {
        echo 'Withdraw'.$value['name'].' '.$value['withdrawType'];
        var_dump(WithdrawAPIService::querySelectors($value['withdrawId'],$value['withdrawType']));
    }
} catch (Exception $e) {
    echo $e->getMessage();
}