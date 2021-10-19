<?php
header("Content-Type:text/html; charset=utf-8");
$root = dirname(dirname(__FILE__));
include_once $root.'/FullPay/util/RSAUtil.php';


$keys = RSAUtil::genKeyPair();
var_dump($keys);
$data = '阿姆我成功了';
var_dump('加密前',$data);
$encryptData = RSAUtil::encryptByPrivateKey($data, $keys['privateKey']);
var_dump('私鑰加密後',$encryptData);
$decryptData = RSAUtil::decryptByPublicKey($encryptData, $keys['publicKey']);
var_dump('公鑰解密後',$decryptData);
$encryptData = RSAUtil::encryptByPublicKey($data, $keys['publicKey']);
var_dump('公鑰加密後',$encryptData);
$decryptData = RSAUtil::decryptByPrivateKey($encryptData, $keys['publicKey']);
var_dump('私鑰解密後',$decryptData);
