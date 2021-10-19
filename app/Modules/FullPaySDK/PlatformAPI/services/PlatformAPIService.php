<?php
namespace FullPay\Platform\services;

use Exception;
use FullPay\util\RSAUtil;
use FullPay\util\URLRequest;
use FullPay\config\FullPayConfig;
/**
 *
 * @author Clare
 *
 */
class PlatformAPIService
{

    const CreateOrderViewPath = '/PlatformAPI/CreateOrderView';

    const QueryMerchantTypePath = '/PlatformAPI/MerchantType';

    const AddMerchantTypePath = '/PlatformAPI/AddMerchantType';

    const ModifyMerchantTypePath = '/PlatformAPI/ModifyMerchantType';
    const QueryPaymentMerchantPath = '/PlatformAPI/PaymentMerchant';
    const QueryWithdrawMerchantPath = '/PlatformAPI/WithdrawMerchant';

    // 接受請求時間在前後3分鐘內
    const vaildTime1 = 180000;

    // 接受請求時間在前後3分鐘內
    const vaildTime2 = - 180000;

    /**
     * FullPayConfig
     *
     * @var FullPayConfig
     */
    public $config = null;

    public function __construct($PlatformManageUrl, $APIServerUrl, $PlatformId, $PublicKey)
    {
        $this->config = new FullPayConfig($PlatformManageUrl, $APIServerUrl, $PlatformId, $PublicKey);
    }

    /**
     * 取得產生訂單畫面網址
     *
     * @return string
     */
    public function getCreateOrderViewUrl()
    {
        return $this->config->PlatformManageUrl . self::CreateOrderViewPath . '/' . $this->config->PlatformId;
    }

    /**
     * 加密資料
     * @param array $array
     * @return string
     */
    public function buildData(array $array)
    {
        return (string) $this->encrypt(json_encode($array));
    }



    /**
     * 查詢支付商戶
     *
     * @param string $paymentId
     * @throws Exception
     * @return array
     */
    public function queryPaymentMerchant($paymentId = null)
    {
        if (!isset($paymentId)) {
            $paymentId = '';
        }
        if (is_null($paymentId)) {
            $paymentId = '';
        }
        if (empty($paymentId)) {
            $paymentId = '';
        }
        $url = $this->config->PlatformManageUrl . self::QueryPaymentMerchantPath . '/' . $this->config->PlatformId;
        $result = URLRequest::post($url, array(
            'data' => $this->encrypt($paymentId)
        ));
        $result = (array) json_decode($result, true);
        return (array) $result['data'];
    }

    /**
     * 查詢代付商戶
     *
     * @param string $withdrawId
     * @throws Exception
     * @return array
     */
    public function queryWithdrawMerchant($withdrawId = null)
    {
        if (!isset($withdrawId)) {
            $withdrawId = '';
        }
        if (is_null($withdrawId)) {
            $withdrawId = '';
        }
        if (empty($withdrawId)) {
            $withdrawId = '';
        }
        $url = $this->config->PlatformManageUrl . self::QueryWithdrawMerchantPath . '/' . $this->config->PlatformId;
        $result = URLRequest::post($url, array(
            'data' => $this->encrypt($withdrawId)
        ));
        $result = (array) json_decode($result, true);
        return (array) $result['data'];
    }

    /**
     * 查詢商戶類型
     *
     * @param string $data
     * @throws Exception
     * @return array
     */
    public function queryMerchantType()
    {
        $url = $this->config->PlatformManageUrl . self::QueryMerchantTypePath . '/' . $this->config->PlatformId;

        $result = URLRequest::post($url);
        $result = (array) json_decode($result, true);
        return (array) $result['data'];
    }

    /**
     * 新增
     *
     * @param unknown $id
     *            商戶類型ID
     * @param unknown $name
     *            商戶類型名稱
     * @param unknown $description
     *            描述
     * @throws Exception
     * @return array
     */
    public function addMerchantType($id, $name, $description)
    {
        if (is_null($id)) {
            throw new Exception(__FUNCTION__ . ' id is null');
        }
        if (is_null($name)) {
            throw new Exception(__FUNCTION__ . ' name is null');
        }
        $data = array(
            'id' => array(
                'id' => $id,
                'platformId' => $this->config->PlatformId
            ),
            'name' => $name,
            'description' => $description
        );
        $url = $this->config->PlatformManageUrl . self::AddMerchantTypePath . '/' . $this->config->PlatformId;
        $result = URLRequest::post($url, array(
            'data' => $this->encrypt(json_encode($data))
        ));
        return json_decode($result)->message;
    }

    /**
     * 修改
     *
     * @param unknown $id
     *            商戶類型ID
     * @param unknown $name
     *            商戶類型名稱
     * @param unknown $description
     *            描述
     * @throws Exception
     * @return array
     */
    public function modifyMerchantType($id, $name, $description)
    {
        if (is_null($id)) {
            throw new Exception(__FUNCTION__ . ' id is null');
        }
        if (is_null($name)) {
            throw new Exception(__FUNCTION__ . ' name is null');
        }
        $data = array(
            'id' => array(
                'id' => $id,
                'platformId' => $this->config->PlatformId
            ),
            'name' => $name,
            'description' => $description
        );
        $url = $this->config->PlatformManageUrl . self::ModifyMerchantTypePath . '/' . $this->config->PlatformId;
        $result = URLRequest::post($url, array(
            'data' => $this->encrypt(json_encode($data))
        ));
        return json_decode($result)->message;
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