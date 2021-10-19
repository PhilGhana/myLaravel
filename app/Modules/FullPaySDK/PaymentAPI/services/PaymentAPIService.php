<?php
namespace FullPay\PaymentAPI\services;

use App\Models\Franchisee;
use Exception;
use FullPay\util\RSAUtil;
use FullPay\util\URLRequest;
use FullPay\config\FullPayConfig;
use FullPay\PaymentAPI\models\Payment;
use FullPay\PaymentAPI\models\OrderForm;
use FullPay\PaymentAPI\models\Order;
use FullPay\PaymentAPI\models\Platform;

/**
 *
 * @author Clare
 *
 */
class PaymentAPIService
{
    const CreateOrderPath = '/PaymentAPI/CreateOrder';
    const QueryOrderPath = '/PaymentAPI/GetOrder';
    const QueryCanUsePath = '/PaymentAPI/QueryCanUse';
    const QuerySelectorsPath = '/PaymentAPI/QuerySelectors';
    const QueryPlatformsPath = '/PaymentAPI/Platforms';

    // 接受請求時間在前後3分鐘內
    const vaildTime1 = 180000;

    // 接受請求時間在前後3分鐘內
    const vaildTime2 = - 180000;

    public $config = null;

    public function __construct($PlatformManageUrl, $APIServerUrl, $PlatformId, $PublicKey)
    {
        $this->config = new FullPayConfig($PlatformManageUrl, $APIServerUrl, $PlatformId, $PublicKey);
    }
    /**
     * 取得產生訂單網址
     * @return string
     */
    public function getCreateOrderUrl()
    {
        return $this->config->APIServerUrl . static::CreateOrderPath . '/' . $this->config->PlatformId;
    }

    /**
     * 加密資料
     *
     * @param \FullPay\PaymentAPI\models\OrderForm $order 支付訂單
     * @return string 加密後的支付訂單資料
     */
    public function buildData(OrderForm $order)
    {
        return (string) $this->encrypt(json_encode($order->toArray()));
    }

    /**
     * 取得通知訂單
     *
     * @throws Exception
     * @return \FullPay\PaymentAPI\models\Order
     */
    public function getCallbackOrder()
    {
        $data = $this->getData();
        $time = time() - $data['flagTime'] / 1000;

        if ($time > static::vaildTime1 || $time < static::vaildTime2) {
            throw new Exception('request is timeout');
        }
        $data['flagTime'] = time() * 1000;
        return $this->queryOrder($data);
    }

    /**
     * 取得http Request data
     *
     * @throws Exception
     * @return array
     */
    public function getData()
    {
        $encryptData = $_POST['data'];
        if (! isset($encryptData)) {
            $encryptData = $_GET['data'];
        }
        if (is_null($encryptData)) {
            throw new Exception(__FUNCTION__ . ' data is null');
        }
        if (empty($encryptData)) {
            throw new Exception(__FUNCTION__ . ' data is empty');
        }
        $decryptData = $this->decrypt($encryptData);
        if (empty($decryptData)) {
            throw new Exception(__FUNCTION__ . ' decrypt data fail');
        }
        return (array) json_decode($decryptData, true);
    }

    /**
     * 查詢訂單
     *
     * @param string $data
     * @throws Exception
     * @return \FullPay\PaymentAPI\models\Order
     */
    public function queryOrder($data)
    {
        if (is_null($data)) {
            throw new Exception(__FUNCTION__ . ' data is null');
        }
        if (empty($data)) {
            throw new Exception(__FUNCTION__ . ' data is empty');
        }

        $url = $this->config->APIServerUrl . static::QueryOrderPath . '/' . $this->config->PlatformId;

        $result = URLRequest::post($url, array(
            'data' => $this->encrypt(json_encode($data))
        ));
        $result = json_decode($result);
        $encryptData = $result->data;
        $data = $this->decrypt($encryptData);
        return new Order(json_decode($data, true));
    }

    /**
     * 查詢可以使用的第三方平台
     *
     * @param string $type
     * @return array
     */
    /**
     * Undocumented function
     *
     * @param string $type
     * @param string $account 帳號
     * @param boolean $showSurpass
     * @return \FullPay\PaymentAPI\models\Payment[]
     */
    public function queryCanUse($type, $account='', $showSurpass=false)
    {
        $url = $this->config->APIServerUrl . static::QueryCanUsePath . '/' . $this->config->PlatformId;
        $result = URLRequest::get($url, array(
            'type' => $type,
            'account' => $account,
            'showSurpass' => $showSurpass,
        ));
        $data = json_decode($result, true);
        return array_map(function($d) {
            return new Payment($d);
        }, (array) $data['data']);
    }

    /**
     * 查詢必填欄位
     * (查詢支付方式銀行代碼)
     *
     * @param string $paymentId
     * @param string $paymentType
     * @throws Exception
     * @return array
     */
    public function querySelectors($paymentId, $paymentType)
    {
        if (is_null($paymentId)) {
            throw new Exception(__FUNCTION__ . ' paymentId is null');
        }
        if (is_null($paymentType)) {
            throw new Exception(__FUNCTION__ . ' paymentType is null');
        }
        $url = $this->config->APIServerUrl . static::QuerySelectorsPath;
        $result = URLRequest::post($url, array(
            'paymentId' => $paymentId,
            'paymentType' => $paymentType
        ));
        $data = json_decode($result, true);
        return (array) $data['data'];
    }

    /**
     * 查詢第三方平台
     *
     * @param string $type
     * @return \FullPay\PaymentAPI\models\Platform[]
     */
    public function queryPlatforms()
    {
        $url = $this->config->APIServerUrl . static::QueryPlatformsPath;
        $result = URLRequest::get($url);
        $data = json_decode($result, true);
        return array_map(function($row) {
            return new Platform($row);
        }, (array) $data['data']);
    }

    /**
     * 接收通知處理成功
     */
    public function success($message,$data=false){
        $result =  array(
            'status' => true,
            'message' => $message
        );
        if($data){
            $result['data'] = $this->encrypt(json_encode($data));
        }
        return $result;
    }

    /**
     * 接收通知處理失敗
     */
    public static function fail($message){
        return array(
            'status' => false,
            'message' => $message
        );
    }

    /**
     * 解密
     *
     * @param string $data
     * @throws Exception
     * @return string
     */
    public function decrypt($encryptStr)
    {
        global $root;
        if (is_null($encryptStr)) {
            throw new Exception(__FUNCTION__ . ' data is null');
        }
        if (empty($encryptStr)) {
            throw new Exception(__FUNCTION__ . ' data is empty');
        }

        $data = (string) RSAUtil::decryptByPublicKey($encryptStr, $this->getPublicKey());
        if (empty($data)) {
            throw new Exception(' decrypt data fail');
        }
        return (string) $data;
    }

    /**
     * 加密
     *
     * @param string $data
     * @throws Exception
     * @return string
     */
    public function encrypt($str)
    {
        if (is_null($str)) {
            throw new Exception(__FUNCTION__ . ' $str is null');
        }
        if (empty($str)) {
            throw new Exception(__FUNCTION__ . ' $str is empty');
        }
        $data = (string) RSAUtil::encryptByPublicKey($str, $this->getPublicKey());
        if (empty($data)) {
            throw new Exception(' encrypt data fail');
        }
        return (string) $data;
    }

    /**
     * 讀取公鑰
     *
     * @return unknown
     */
    private function getPublicKey()
    {
        $fp = fopen($this->config->PublicKey, "r");
        $publicKey = fread($fp, 1024);
        fclose($fp);
        return $publicKey;
    }
}