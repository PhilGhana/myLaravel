<?php
namespace FullPay\util;

class RSAUtil
{
    const MAX_ENCRYPT_BLOCK = 117;
    const MAX_DECRYPT_BLOCK = 128;

    public static function genKeyPair(){
        $resource = openssl_pkey_new(array(
            'private_key_bits' => 1024,      // Size of Key.
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ));
        openssl_pkey_export($resource, $privateKey);
        $detail = openssl_pkey_get_details($resource);
        $publicKey = $detail['key'];
        return array(
            'privateKey'=>$privateKey,
            'publicKey'=>$publicKey
        );
    }

    public static function encryptByPublicKey($data, $publicKey)
    {
        $encrypted = '';
        $parts = str_split($data, self::MAX_ENCRYPT_BLOCK);
        foreach ($parts as $part) {
            $encrypted_temp = '';
            openssl_public_encrypt($part, $encrypted_temp, $publicKey);
            $encrypted .= $encrypted_temp;
        }
        return base64_encode($encrypted);
    }

    public static function decryptByPublicKey($data, $publicKey)
    {
        $decrypted = '';
        $parts = str_split(base64_decode($data), self::MAX_DECRYPT_BLOCK);
        foreach ($parts as $part) {
            $decrypted_temp = '';
            openssl_public_decrypt($part, $decrypted_temp,$publicKey);
            $decrypted .= $decrypted_temp;
        }
        return $decrypted;
    }

    public static function encryptByPrivateKey($data, $privateKey)
    {
        $encrypted = '';
        $parts = str_split($data, self::MAX_ENCRYPT_BLOCK);
        foreach ($parts as $part) {
            $encrypted_temp = '';
            openssl_private_encrypt($part, $encrypted_temp, $privateKey);
            $encrypted .= $encrypted_temp;
        }
        return base64_encode($encrypted);
    }

    public static function decryptByPrivateKey($data, $privateKey)
    {
        $decrypted = '';
        $base64_decoded = base64_decode($data);
        $parts = str_split($base64_decoded, self::MAX_DECRYPT_BLOCK);
        foreach ($parts as $part) {
            $decrypted_temp = '';
            openssl_private_decrypt($part, $decrypted_temp,$privateKey);
            $decrypted .= $decrypted_temp;
        }
        return $decrypted;
    }
}