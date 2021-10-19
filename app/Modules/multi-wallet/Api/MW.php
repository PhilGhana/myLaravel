<?php

namespace MultiWallet\Api;

use MultiWallet\Api\Config\MWConfigConstract;

use MultiWallet\Base\BaseMultiWalletInterface;
use MultiWallet\Params\MemberParameter;
use MultiWallet\Params\TransferParameter;
use MultiWallet\Params\LaunchGameParameter;
use MultiWallet\Params\SyncReportParameters;
use MultiWallet\Params\SyncCallBackParameter;

use MultiWallet\Feedback\MemberFeedback;
use MultiWallet\Feedback\TransferFeedback;
use MultiWallet\Feedback\BalanceFeedback;
use MultiWallet\Feedback\LaunchGameFeedback;
use MultiWallet\Feedback\SyncCallBackFeedback;

use MultiWallet\Exceptions\LoginException;
use MultiWallet\Exceptions\GameListException;

use App\Models\Report;
use Carbon\Carbon;

class MW extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;
    protected $siteId;
    protected $private_key;
    protected $MW_public_key;
    protected $utoken;

    public function __construct(array $config)
    {
        $this->config = new MWConfigConstract();
        $this->siteId = $this->config["siteId"];
        $this->private_key = openssl_pkey_get_private($this->config["ecPrivateKey"]);
        $this->MW_public_key = $this->config["mwPublicKey"];
    }

    /**
     * 建立會員
     * 藉由 UserInfo api 執行授權
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $arr_json = [
            "uid" => $member->playerId,
            "utoken" => $this->getToken(),
            "timestamp" => (Carbon::now())->timestamp,
        ];

        $apiUrl = '/api/userInfo';
        $memberFeedback = new MemberFeedback();
        $result = $this->sentApi($memberFeedback, $apiUrl, 'userInfo', $arr_json);

        if ($result instanceof MemberFeedback) {
            return $result;
        }

        // 發生錯誤
        if ($result["ret"] !== "0000") {
            $memberFeedback->error_code = $result["ret"];
            $memberFeedback->error_msg = $result["msg"];

            return $memberFeedback;
        }

        // agentName 同等於 uid
        $memberFeedback->extendParam = $result["agentName"];

        return $memberFeedback;
    }

    /**
     * 取得會員餘額
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        // 未有接口，為了提忖款功能正常，回傳"0"
        $balanceFeedback = new BalanceFeedback();
        $balanceFeedback->response_code = 200;
        $balanceFeedback->balance = 0;

        return $balanceFeedback;
    }

    /**
     * 存款
     * 必須貨幣轉入準備，再貨幣轉入確認
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        // 單號
        $orderNo = md5($transfer->member->playerId . time());

        // 北京時區
        $tz = 8;
        $orderTime = Carbon::createFromTimeStampUTC($tz);

        $ip = $_SERVER['SERVER_ADDR'];
        $arr_json = [
            "uid" => $transfer->member->playerId,
            "utoken" => $this->getToken(),
            "transferType" => "0",
            "transferAmount" => $transfer->amount,
            "transferOrderNo" => $orderNo,
            "transferOrderTime" => $orderTime->format('yyyy-MM-dd HH:mm:ss'),
            "transferClientIp" => $ip,
            "timestamp" => (Carbon::now())->timestamp,
        ];

        // 轉入準備
        $apiUrl = '/api/transferPrepare';
        $transferFeedback = new TransferFeedback();
        $result = $this->sentApi($transferFeedback, $apiUrl, 'transferPrepare', $arr_json);

        if ($result instanceof TransferFeedback) {
            return $result;
        }

        // 發生錯誤
        if ($result["ret"] !== "0000") {
            $transferFeedback->error_code = $result["ret"];
            $transferFeedback->error_msg = $result["msg"];

            return $transferFeedback;
        }

        // 轉入確認
        $asinTransferOrderNo = $result["asinTransferOrderNo"];
        $asinTransferDate = $result["asinTransferDate"];
        $arr_json = [
            "uid" => $transfer->member->playerId,
            "utoken" => $this->getToken(),
            "asinTransferOrderNo" => $asinTransferOrderNo,
            "asinTransferOrderTime" => $asinTransferDate,
            "transferOrderNo" => $orderNo,
            "transferAmount" => $transfer->amount,
            "transferClientIp" => $ip,
            "timestamp" => (Carbon::now())->timestamp,
        ];

        $apiUrl = '/api/transferPay';
        $transferFeedback = new TransferFeedback();
        $result = $this->sentApi($transferFeedback, $apiUrl, 'transferPay', $arr_json);

        if ($result instanceof TransferFeedback) {
            return $result;
        }

        // 轉入確認
        if ($result["ret"] === "0000") {
            $transferFeedback->balance = $transfer->amount;
            $transferFeedback->remote_payno = $asinTransferOrderNo;
            $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }

        // 發生錯誤
        $transferFeedback->error_code = $result["ret"];
        $transferFeedback->error_msg = $result["msg"];

        return $transferFeedback;
    }

    /**
     * 提款
     * 必須貨幣轉出準備，再貨幣轉出確認
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        // 單號
        $orderNo = md5($transfer->member->playerId . time());

        // 北京時區
        $tz = 8;
        $orderTime = Carbon::createFromTimeStampUTC($tz);

        $ip = $_SERVER['SERVER_ADDR'];
        $arr_json = [
            "uid" => $transfer->member->playerId,
            "utoken" => $this->getToken(),
            "transferType" => "1",
            "transferAmount" => $transfer->amount,
            "transferOrderNo" => $orderNo,
            "transferOrderTime" => $orderTime->format('yyyy-MM-dd HH:mm:ss'),
            "transferClientIp" => $ip,
            "timestamp" => (Carbon::now())->timestamp,
        ];

        // 轉入準備
        $apiUrl = '/api/transferPrepare';
        $transferFeedback = new TransferFeedback();
        $result = $this->sentApi($transferFeedback, $apiUrl, 'transferPrepare', $arr_json);

        if ($result instanceof TransferFeedback) {
            return $result;
        }

        // 發生錯誤
        if ($result["ret"] !== "0000") {
            $transferFeedback->error_code = $result["ret"];
            $transferFeedback->error_msg = $result["msg"];

            return $transferFeedback;
        }

        // 轉入確認
        $asinTransferOrderNo = $result["asinTransferOrderNo"];
        $asinTransferDate = $result["asinTransferDate"];
        $arr_json = [
            "uid" => $transfer->member->playerId,
            "utoken" => $this->getToken(),
            "asinTransferOrderNo" => $asinTransferOrderNo,
            "asinTransferOrderTime" => $asinTransferDate,
            "transferOrderNo" => $orderNo,
            "transferAmount" => $transfer->amount,
            "transferClientIp" => $ip,
            "timestamp" => (Carbon::now())->timestamp,
        ];

        $apiUrl = '/api/transferPay';
        $transferFeedback = new TransferFeedback();
        $result = $this->sentApi($transferFeedback, $apiUrl, 'transferPay', $arr_json);

        if ($result instanceof TransferFeedback) {
            return $result;
        }

        // 轉入確認
        if ($result["ret"] === "0000") {
            $transferFeedback->balance = -($transfer->amount);
            $transferFeedback->remote_payno = $asinTransferOrderNo;
            $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }

        // 發生錯誤
        $transferFeedback->error_code = $result["ret"];
        $transferFeedback->error_msg = $result["msg"];

        return $transferFeedback;
    }

    /**
     * 站點流水日誌 (注單資料)
     *
     * @return void
     */
    public function syncReport(SyncReportParameters $srp, callable $callback)
    {
        // 北京時區
        $tz = 'Asia/Shanghai';
        $beginTime = Carbon::parse($srp->startAt, $tz)->format('yyyy-MM-dd HH:mm:ss');
        $endTime = Carbon::parse($srp->endAt, $tz)->format('yyyy-MM-dd HH:mm:ss');
        $params = [
            'beginTime' => $beginTime,
            'endTime' => $endTime,
            'page' => 1,
        ];

        $callback($this->doSyncReport($params));
    }

    /**
     * @param array $params
     */
    private function doSyncReport($params)
    {
        $apiUrl = '/as-service/api/siteUsergamelog';
        $callBackFeedback = new SyncCallBackFeedback();
        $result = $this->sentApi($callBackFeedback, $apiUrl, 'siteUsergamelog', $params);

        if ($result instanceof SyncCallBackFeedback) {
            return $result;
        }

        if ($result["ret"] === '0000') {
            $rows = $result["userGameLogs"];
            $data = [];

            foreach ($rows as $row) {
                $data[] = $this->makeSyncCallBackParameter($row);
            }

            if ($result["total"] > $params["page"]) {
                $params["page"] = $params["page"] + 1;
                $data = array_merge($data, $this->doSyncReport($params));
            }

            return $data;
        }

        $callBackFeedback->error_code = $result["ret"];
        $callBackFeedback->error_msg = $result["msg"];

        return $callBackFeedback;
    }

    /**
     * @param array $row
     */
    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid = $row["gameNum"];
        $callBackParam->username = $row["uid"];
        $callBackParam->betAmount = $row["initBet"];
        $callBackParam->validAmount = $row["playMoney"];
        $callBackParam->gameCode = $row["gameId"];
        $callBackParam->winAmount = $row["winMoney"];
        $callBackParam->reportAt = $row["logDate"];
        $callBackParam->content = $row["exInfo"];

        // 沒有注單狀態，無法判斷是否退款
        // $callBackParam->status = '';

        return $callBackParam;
    }


    /**
     * 發送
     *
     * @param BaseFeedback  $feedback
     * @param string        $apiUrl
     * @param string        $func
     * @param array         $arr_json
     */
    private function sentApi($feedback, $apiUrl, $func, $arr_json)
    {
        // # data數據規則 step 3:
        $signContent = getSignContentString($arr_json);
        openssl_sign($signContent, $out, $this->private_key);

        // RSA签名
        $sign = base64_encode($out);

        // 移除多餘斜線
        $sign = str_replace("\\", "", $sign);

        // # data數據規則 step 4:
        // 加入 sign 組成 jsonString
        $arr_json["sign"] = $sign;
        $json_str = json_encode($arr_json);

        // # data數據規則 step 5:
        // EC Platform AES Key 生成
        $AES_key = $this->getAESkey();

        // # data數據規則 step 6: // AES加密
        $data = self::aes_encript($AES_key, $json_str);

        // key數據規則1:
        // 沿用data step5 的  aes key

        // key數據規則2:
        openssl_public_encrypt($AES_key, $key, $this->MW_public_key);

        // RSA加密
        $key = base64_encode($key);

        // 過濾特殊字符
        $key = str_replace("\\", "", $key);

        $data = urlencode($data);
        $key = urlencode($key);
        $fullParams = [
                "func" => $func,
                "resultType" => "json",
                "lang" => $this->getLocale(),
                "siteId" => $this->siteId,
                "data" => $data,
                "key" => $key,
            ];
        $response = $this->post($apiUrl, $fullParams, false);
        $result = json_decode($response, true);

        // 如果解不開，就直接把錯誤丟回去
        if (is_null($result)) {
            $feedback->error_code = static::ENCRYPT_ERROR;
            $feedback->error_msg = $response;

            return $feedback;
        }

        // 肯定出問題了
        if ($this->reponseCode != 200) {
            $feedback->error_code = static::RESPONSE_ERROR;
            $feedback->response_code = $this->reponseCode;
            $feedback->error_msg = '對方似乎報錯:' . $this->reponseCode;

            return $feedback;
        }

        return $result;
    }

    /**
     * 按照字母排序，然後再串接
     *
     * @param array $dataArray
     * @return string
     */
    private function getSignContentString($dataArray)
    {
        $signContent = null;
        ksort($dataArray);

        foreach ($dataArray as $key => $value) {
            $value = (is_null($value))?'':$value;
            $signContent = $signContent . $key . "=" . $value;
        }

        return $signContent;
    }

    /**
     * 生成16位 aes key
     *
     * @return string
     */
    private function getAESkey()
    {
        $aes = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol) - 1;

        // 串接隨機整數
        for ($count = 0; $count < 16; $count++) {
            $aes = $aes . $strPol[rand(0, $max)];
        }

        return $aes;
    }

    /**
     * 取得語系
     */
    private function getLocale()
    {
        $langs = [
                'cn',
                'hk',
                'en',
            ];
        $lang = (in_array(app()->getLocale(), $langs))?app()->getLocale():'cn';

        return $lang;
    }

    /**
     * 取得授權碼
     */
    private function getToken()
    {
        if (!is_null($this->utoken)) {
            return $this->utoken;
        }
        $this->utoken = md5($this->getAESkey() . time());

        return $this->utoken;
    }

    /**
     *  加密
     *
     * @param string $key
     * @param string $str
     */
    public static function aes_encript($key, $str)
    {
        $encrypted = openssl_encrypt($str, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        $data = base64_encode($encrypted);

        return $data;
    }

    /**
     * 解密
     *
     * @param string $key
     * @param string $str
     */
    public static function aes_decript($key,$str)
    {
        $encryptedData = base64_decode($str);
        $decrypted = openssl_decrypt($encryptedData, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);

        return $decrypted;
    }
}
