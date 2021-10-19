<?php
namespace FullPay\util;

class AESUtil
{

    public static function encrypt($str, $key)
    {
        $key =  base64_decode($key);
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $str = self::pkcs5Pad($str, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $str);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    public static function decrypt($str, $key)
    {
        $key =  base64_decode($key);
        $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($str), MCRYPT_MODE_ECB);
        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s - 1]);
        $decrypted = substr($decrypted, 0, - $padding);
        return $decrypted;
    }

    public static function pkcs5Pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
}