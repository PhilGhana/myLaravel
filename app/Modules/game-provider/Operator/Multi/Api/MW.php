<?php

namespace GameProvider\Operator\Multi\Api;

use GameProvider\Operator\BaseApi;

use GameProvider\Operator\Multi\Config\MWConfigConstract;

use GameProvider\Operator\Multi\BaseMultiWalletInterface;

use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\TransferParameter;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;

use GameProvider\Operator\Feedback\MemberFeedback;
use GameProvider\Operator\Feedback\TransferFeedback;
use GameProvider\Operator\Feedback\BalanceFeedback;
use GameProvider\Operator\Feedback\LaunchGameFeedback;
// use MultiWallet\Feedback\SyncCallBackFeedback;


use GameProvider\Exceptions\AesException;
use GameProvider\Exceptions\LoginException;
use GameProvider\Exceptions\GameListException;
use GameProvider\Exceptions\JSONException;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\CreateMemberException;
use GameProvider\Exceptions\LaunchGameException;
use GameProvider\Exceptions\SyncException;
use GameProvider\Exceptions\TransferException;

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
        $result = $this->sentApi($apiUrl, 'userInfo', $arr_json);

        // 發生錯誤
        if ($result->ret !== "0000") {
            throw new CreateMemberException(get_class($this), 'create member error! error code : ' . $result->ret, $result->msg);
        }

        // agentName 同等於 uid
        $userInfo = array_shift($result->userInfo);
        $memberFeedback->extendParam = $userInfo->agentName;

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
        $result = $this->sentApi($apiUrl, 'transferPrepare', $arr_json);

        // 發生錯誤
        if ($result->ret !== "0000") {
            throw new TransferException(get_class($this), 'deposit prepare error! error code : ' . $result->ret, $result->msg);
        }

        // 轉入確認
        $asinTransferOrderNo = $result->asinTransferOrderNo;
        $asinTransferDate = $result->asinTransferDate;
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
        $result = $this->sentApi($apiUrl, 'transferPay', $arr_json);

        // 轉入確認
        if ($result->ret === "0000") {
            $transferFeedback->balance = $transfer->amount;
            $transferFeedback->remote_payno = $asinTransferOrderNo;
            $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }

        // 發生錯誤
        throw new TransferException(get_class($this), 'deposit pay error! error code : ' . $result->ret, $result->msg);
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
        $result = $this->sentApi($apiUrl, 'transferPrepare', $arr_json);

        // 發生錯誤
        if ($result->ret !== "0000")
        {
            throw new TransferException(get_class($this), 'withdraw prepare error! error code : ' . $result->ret, $result->msg);
        }

        // 轉入確認
        $asinTransferOrderNo = $result->asinTransferOrderNo;
        $asinTransferDate = $result->asinTransferDate;
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
        $result = $this->sentApi($apiUrl, 'transferPay', $arr_json);

        // 轉入確認
        if ($result->ret === "0000") {
            $transferFeedback->balance = -($transfer->amount);
            $transferFeedback->remote_payno = $asinTransferOrderNo;
            $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }

        // 發生錯誤
        throw new TransferException(get_class($this), 'withdraw pay error! error code : ' . $result->ret, $result->msg);
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

        $result = $this->sentApi($apiUrl, 'siteUsergamelog', $params);

        if ($result->ret === '0000') {
            $rows = $result->userGameLogs;
            $data = [];

            foreach ($rows as $row) {
                $data[] = $this->makeSyncCallBackParameter($row);
            }

            if ($result->total > $params["page"]) {
                $params["page"] = $params["page"] + 1;
                $data = array_merge($data, $this->doSyncReport($params));
            }

            return $data;
        }

        throw new SyncException(get_class($this), 'sync error! error code : ' . $result->ret, $result->msg);
    }

    /**
     * @param array $row
     */
    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid = $row["gameNum"];
        $callBackParam->username = $row["uid"];
        // $callBackParam->betAmount = $row["initBet"];
        $callBackParam->betAmount = $row["playMoney"];
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
     * @param string        $apiUrl
     * @param string        $func
     * @param array         $arr_json
     */
    private function sentApi($apiUrl, $func, $arr_json)
    {
        // # data數據規則 step 3:
        $signContent = $this->getSignContentString($arr_json);
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
        $result = json_decode($response);

        // 如果解不開，就直接把錯誤丟回去
        if($result === null)
        {
            throw new JSONException(get_class($this), 'error on JSON decode !', $result);
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

        if($encrypted === false)
        {
            throw new AesException(get_class($this), 'error on AES encrypt !', $str);
        }

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

        if($decrypted === false)
        {
            throw new AesException(get_class($this), 'error on AES decode !', $str);
        }

        return $decrypted;
    }

    /**
     * 獲取遊戲列表
     *
     * @return void
     */
    public function getGameList()
    {
        $arr_json = [
            "timestamp" => (Carbon::now())->timestamp,
        ];

        $apiUrl = 'api/gameInfo';
        $result = $this->sentApi($apiUrl, 'gameIbfo', $arr_json);
        $gameList = [];

        if($result->ret == "0000") {
            $gameList = $result->games;

            return $gameList;
        }

        throw new GameListException(get_class($this), 'Error when start getting game list error code : ' . $result->ret);
    }

    /**
     * 會員登入（取得遊戲路徑）
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $domain = '';
        if($this->getDomain()->ret == "0000") {
            $domain = $this->getDomain()->domain;
        }else{
            throw new LaunchGameException(get_class($this), 'Domain error! error code : ' . $this->getDomain()->ret, $this->getDomain()->msg);
        };

        $arr_json = [
           "uid" => $launchGameParams->member->playerId,
           "utoken" => $this->getToken(),
           "timestamp" => (Carbon::now())->timestamp,
        ];

        $launchGameFeedback = new LaunchGameFeedback();
        $apiUrl = 'api/oauth';
        $result = $this->sentApi($apiUrl, 'oauth', $arr_json);

        if ($result->ret == "0000") {
            $launchGameFeedback->gameUrl = $domain . $result->interface;
            $launchGameFeedback->response_code = $this->reponseCode;

            return $launchGameFeedback;
        }

        // 發生錯誤
        throw new LaunchGameException(get_class($this), 'launch game error! error code : ' . $result->ret, $result->msg);
    }

    public function getDomain()
    {
        $arr_json = [
            "timestamp" => (Carbon::now())->timestamp,
        ];

        $apiUrl = 'api/domain';
        $result = $this->sentApi($apiUrl, 'domain', $arr_json);

        return $result;
    }

}
