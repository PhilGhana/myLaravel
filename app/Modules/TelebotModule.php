<?php
namespace App\Modules;

class TelebotModule
{
    protected $publicKey;

    protected $botUid;

    protected $key;

    protected $iv;

    protected $url;

    protected $callbackUrl;

    const ENC_METHOD = 'aes-256-cbc';

    const MESSAGE_CHAR_LIMIT = 4000;

    const CAPTION_CHAR_LIMIT = 1000;

    const ESCAPE_REGEX = '/[_*[`]/';

    private static $instance;

    private function __construct()
    {
        $this->publicKey   = config('site.telebot.public_key');
        $this->botUid      = config('site.telebot.bot_uid');
        $this->callbackUrl = config('site.telebot.callback_url');
        $this->key = config('site.telebot.key');
        $this->iv  = config('site.telebot.iv');
        $this->url = config('site.telebot.host') . '/api/partner/' . $this->publicKey;
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    public function getBotUid()
    {
        return $this->botUid;
    }

    public function encryptData(array $data)
    {
        $json = json_encode($data);
        return [
            'ciphertext' => base64_encode(
                openssl_encrypt($json, self::ENC_METHOD, $this->key, $options = 1, $this->iv)
            ),
            'signature'  => md5($json . $this->key),
        ];
    }

    public function decryptData(array $data)
    {
        $json = openssl_decrypt(
            base64_decode($data['ciphertext'] ?? ''),
            self::ENC_METHOD,
            $this->key,
            $options = 1,
            $this->iv
        );
        if (!$json) {
            throw new \Exception('decrypt ciphertext error!');
        }
        if (md5($json . $this->key) != ($data['signature'] ?? '')) {
            throw new \Exception('decrypt md5 not match');
        }
        return json_decode($json, true);
    }

    public function postData(string $url, array $data)
    {
        $encData    = $this->encryptData($data);
        $postStr    = json_encode($encData);
        $postHeader = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postStr),
        ];
        logger()->debug('Telebot PostData', [
            'post' => $data,
            'enc'  => $encData,
        ]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $this->url . $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $postHeader);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$httpCode) {
            throw new \Exception('Curl Error');
        }
        $data = json_decode($response, true);
        if ($httpCode != 200) {
            if (!$data) {
                logger()->error('telebot response error', [
                    'code'     => $httpCode,
                    'response' => $response,
                ]);
                throw new \Exception("response parse error, code: $httpCode", $httpCode);
            }
            throw new \Exception(json_encode($this->decryptData($data)), $httpCode);
        }
        return $this->decryptData($data);
    }

    public static function limitMessage(string $str = '')
    {
        return str_limit($str, self::MESSAGE_CHAR_LIMIT);
    }

    public static function limitCaption(string $str = '')
    {
        return str_limit($str, self::CAPTION_CHAR_LIMIT);
    }

    public static function escapeText($str)
    {
        if (!$str || !is_string($str)) {
            return $str;
        }
        return preg_replace(self::ESCAPE_REGEX, '\\\\$0', $str);
    }

}
