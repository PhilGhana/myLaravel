<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Game;
use App\Models\Report;
use App\Models\SystemConfig;
use Carbon\Carbon;
use GameProvider\Exceptions\AesException;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\CreateMemberException;
use GameProvider\Exceptions\GameListException;
use GameProvider\Exceptions\JSONException;
use GameProvider\Exceptions\LaunchGameException;
use GameProvider\Exceptions\LoginException;
use GameProvider\Exceptions\SyncException;
use GameProvider\Exceptions\TransferException;
use GameProvider\Operator\BaseApi;
use GameProvider\Operator\Feedback\BalanceFeedback;
use GameProvider\Operator\Feedback\LaunchGameFeedback;
use GameProvider\Operator\Feedback\MemberFeedback;
use GameProvider\Operator\Feedback\TransferFeedback;
use GameProvider\Operator\Multi\BaseMultiWalletInterface;
use GameProvider\Operator\Multi\Config\RCGConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;

class RCG extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $errorMessage = [
        '-1'   => '其它失敗',
        '-2'   => '未預期的例外',
        '-3'   => '請求驗證失敗',
        '-4'   => '會員在線',
        '-5'   => '停用',
        '-6'   => '會員鎖定',
        '-7'   => '會員凍結',
        '-8'   => '無此帳號',
        '-9'   => '遊戲關閉',
        '-10'  => '系統維護',
        '-11'  => '額度異常',
        '-12'  => '公司代碼不存在',
        '-13'  => '找不到資料',
        '-14'  => 'Header 錯誤',
        '-15'  => '簽章錯誤',
        '-16'  => '加密錯誤',
        '-17'  => 'URL 編碼錯誤',
        '-18'  => '紀錄寫入失敗',
        '-19'  => '交易 Type 錯誤',
        '-20'  => '交易流水號已存在',
        '-21'  => 'DB 資料更新錯誤',
        '-22'  => '轉型失敗',
        '-23'  => '查詢 DB 失敗',
        '-24'  => '語系不存在',
        '-25'  => '貨幣不存在',
        '-26'  => '系統代碼不存在',
        '-27'  => '站台代碼不存在',
        '-28'  => '在線人數超過設定上限',
    ];

    public function __construct(array $config)
    {
        $this->config             = new RCGConfigConstract();
        $this->config->apiUrl     = str_finish($config['apiUrl'], '/');
        $this->config->clientID   = $config['clientID'];
        $this->config->secret     = $config['secret'];
        $this->config->desKey     = $config['desKey'];
        $this->config->iv         = $config['iv'];
        $this->config->systemCode = $config['systemCode'];
        $this->config->webId      = $config['webId'];
        // $this->config->stationID  = $config['stationID'];
        $this->config->lang       = $config['lang'];
        $this->config->currency   = $config['currency'];
    }

    public function getGameList()
    {
    }

    /**
     * 建立會員
     *
     * @return void
     */
    public function createMember(MemberParameter $member)
    {
        $apiUrl = $this->config->apiUrl.'api/Player/CreateOrSetUser';

        $params = [
            'UserId'       => $member->playerId,
            'UserName'     => substr($member->playerId, 0, 15),
            'TopBalance'   => -1,
            'GroupLimitId' => '1,2,3',
            'OpenGameList' => 'ALL',
            'Language'     => $this->config->lang,
            'Currency'     => $this->config->currency,
        ];

        $result = $this->doSendProcess($apiUrl, $params);

        if ($result->MsgID != 0) {
            throw new CreateMemberException(get_class($this), 'create member error! error code : '.$result->MsgID, $this->errorMessage[(string) $result->MsgID]);
        }

        return new MemberFeedback();
    }

    /**
     * 存款.
     *
     * @return void
     */
    public function deposit(TransferParameter $transfer)
    {
        $apiUrl = $this->config->apiUrl.'api/Player/Deposit';

        $transactionID = substr($transfer->member->playerId, 0, 10).time();

        $params = [
            'UserId'        => $transfer->member->playerId,
            'TransactionID' => $transactionID,
            'Balance'       => $transfer->amount,
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($apiUrl, $params);

        if ($result->MsgID != 0) {
            throw new TransferException(get_class($this), 'deposit error! error code : '.$result->MsgID, $this->errorMessage[(string) $result->MsgID]);
        }

        $transferFeedback->balance      = $result->Data->CurrentPlayerBalance;
        $transferFeedback->remote_payno = $transactionID;

        return $transferFeedback;
    }

    /**
     * 提款.
     *
     * @return void
     */
    public function withdraw(TransferParameter $transfer)
    {
        $apiUrl = $this->config->apiUrl.'api/Player/Withdraw';

        $transactionID = substr($transfer->member->playerId, 0, 10).time();

        $params = [
            'UserId'        => $transfer->member->playerId,
            'TransactionID' => $transactionID,
            'Balance'       => $transfer->amount,
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($apiUrl, $params);

        if ($result->MsgID != 0) {
            throw new TransferException(get_class($this), 'withdraw error! error code : '.$result->MsgID, $this->errorMessage[(string) $result->MsgID]);
        }

        $transferFeedback->balance      = $result->Data->CurrentPlayerBalance;
        $transferFeedback->remote_payno = $transactionID;

        return $transferFeedback;
    }

    /**
     * 會員登入（取得遊戲路徑）.
     *
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $apiUrl = $this->config->apiUrl.'api/Player/GetURLToken';

        $params = [
            'UserId' => $launchGameParams->member->playerId,
        ];

        $result = $this->doSendProcess($apiUrl, $params);

        if ($result->MsgID != 0) {
            throw new LaunchGameException(get_class($this), 'launchGame token error! error code : '.$result->MsgID, $this->errorMessage[(string) $result->MsgID]);
        }

        $apiUrl = $this->config->apiUrl.'api/Player/Login';

        $params = [
            'Token' => $result->Data->URLToken,
            'lang'  => $this->config->lang,
        ];

        $result = $this->doSendProcess($apiUrl, $params);

        $launchGameFeedback = new LaunchGameFeedback();

        $result = $this->doSendProcess($apiUrl, $params);

        if ($result->MsgID != 0) {
            throw new LaunchGameException(get_class($this), 'launchGame error! error code : '.$result->MsgID, $this->errorMessage[(string) $result->MsgID]);
        }

        $launchGameFeedback->gameUrl       = $result->Data->URL;
        $launchGameFeedback->mobileGameUrl = $result->Data->URL;

        return $launchGameFeedback;
    }

    public function getBalance(MemberParameter $member)
    {
        $apiUrl = $this->config->apiUrl.'api/Player/GetBalance';

        $params = [
            'UserId'     => $member->playerId,
        ];

        $balanceFeedback = new BalanceFeedback();

        $result = $this->doSendProcess($apiUrl, $params);

        if ($result->MsgID != 0) {
            throw new BalanceException(get_class($this), 'get balance error! error code : '.$result->MsgID, $this->errorMessage[(string) $result->MsgID]);
        }

        $balanceFeedback->balance = $result->Data->CurrentPlayerBalance;

        return $balanceFeedback;
    }

    /**
     * 同步注單 (取得遊戲每日統計資訊(全部遊戲類型)).
     *
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $apiUrl = $this->config->apiUrl.'api/Record/GetBetRecordList';

        // 獲取最後一張單的MID來用
        $config = SystemConfig::where('key', 'RCG_MAX_ID')->first();

        $max_mid = 0;
        if ($config) {
            $max_mid = $config->value;
        }

        $params = [
            'MaxId' => $max_mid,
            'Rows'  => 500,
        ];

        $result = $this->doSendProcess($apiUrl, $params);

        if ($result->MsgID != 0) {
            throw new SyncException(get_class($this), 'syncReport error! error code : '.$result->MsgID, $this->errorMessage[(string) $result->MsgID]);
        }

        $data = [];
        $id   = 0;
        foreach ($result->Data->DataList as $row) {
            if ($row->Id >= $id) {
                $id = $row->Id;
            }

            $data[] = $this->makeSyncCallBackParameter($row);
        }

        if ($config) {
            $config->value = $id;
            $config->save();
        } else {
            $systemConfig                = new SystemConfig();
            $systemConfig->key           = 'RCG_MAX_ID';
            $systemConfig->value         = $id;
            $systemConfig->type          = SystemConfig::TYPE_STRING;
            $systemConfig->remark        = '';
            $systemConfig->franchisee_id = 0;
            $systemConfig->save();
        }

        $changeData = $this->syncChangeReport();

        return $callback(array_merge($data, $changeData));
    }

    private function syncChangeReport()
    {
        $apiUrl = $this->config->apiUrl.'api/Record/GetChangeRecordList';

        // 獲取最後一張單的MID來用
        $config = SystemConfig::where('key', 'RCG_CHANGE_MAX_ID')->first();

        $max_mid = 0;
        if ($config) {
            $max_mid = $config->value;
        }

        $params = [
            'MaxId' => $max_mid,
            'Rows'  => 500,
        ];

        $result = $this->doSendProcess($apiUrl, $params);

        if ($result->MsgID != 0) {
            throw new SyncException(get_class($this), 'syncReport error! error code : '.$result->MsgID, $this->errorMessage[(string) $result->MsgID]);
        }

        $data = [];
        $id   = 0;
        foreach ($result->Data->DataList as $row) {
            if ($row->Id >= $id) {
                $id = $row->Id;
            }
            $data[] = $this->makeSyncCallBackParameter($row);
        }

        if ($config) {
            $config->value = $id;
            $config->save();
        } else {
            $systemConfig                = new SystemConfig();
            $systemConfig->key           = 'RCG_CHANGE_MAX_ID';
            $systemConfig->value         = $id;
            $systemConfig->type          = SystemConfig::TYPE_STRING;
            $systemConfig->remark        = '';
            $systemConfig->franchisee_id = 0;
            $systemConfig->save();
        }

        return $data;
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid         = $row->Id;
        $callBackParam->username    = $row->UserId;
        $callBackParam->betAmount   = $row->Bet;
        $callBackParam->validAmount = $row->Available;
        $callBackParam->gameCode    = 'RCG';
        $callBackParam->winAmount   = $row->WinLose + $row->Bet;
        $callBackParam->betAt       = $row->DateTime;
        $callBackParam->reportAt    = $row->DateTime;
        $callBackParam->settleAt    = $row->ReportDT ?? null;
        $callBackParam->ip          = $row->IP;
        $callBackParam->table       = $row->Desk;
        $callBackParam->round       = $row->RunNo;
        $callBackParam->status      = Report::STATUS_COMPLETED;

        return $callBackParam;
    }

    private function doSendProcess($apiUrl, $params)
    {
        $params['SystemCode'] = $this->config->systemCode;
        $params['WebId']      = $this->config->webId;

        $params               = $this->setParams($params);

        $timestamp        = time();
        $this->curlHeader = [
            'Content-Type:application/x-www-form-urlencoded',
            'X-API-ClientID:'.$this->config->clientID,
            'X-API-Timestamp:'.$timestamp,
            'X-API-Signature:'.base64_encode(md5($this->config->clientID.$this->config->secret.$timestamp.$params, true)),
        ];

        $response = $this->post($apiUrl, urlencode($params), false);

        $decode = json_decode($this->decrypt($response));

        return $decode;
    }

    private function setParams($params)
    {
        return $this->encrypt($params);
    }

    private function encrypt($data)
    {
        $result = openssl_encrypt(json_encode($data), 'DES-CBC', $this->config->desKey, OPENSSL_RAW_DATA, $this->config->iv);

        if ($result === false) {
            throw new AesException(get_class($this), 'error on AES encrypt !', json_encode($data));
        }

        return base64_encode($result);
    }

    private function decrypt($data)
    {
        $encrypted = base64_decode($data);

        $decode = openssl_decrypt($encrypted, 'DES-CBC', $this->config->desKey, OPENSSL_RAW_DATA, $this->config->iv);

        if ($decode === false) {
            throw new AesException(get_class($this), 'error on AES decode !', $data);
        }

        return $decode;
    }
}
