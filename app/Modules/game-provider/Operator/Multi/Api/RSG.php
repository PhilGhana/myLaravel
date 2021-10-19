<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Report;
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
use GameProvider\Operator\Multi\Config\RSGConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
// use MultiWallet\Feedback\SyncCallBackFeedback;

use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;

class RSG extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $token = null;

    protected $errorMessage = [
        '0'    => '正常',
        '1001' => '執行失敗',
        '1002' => '系統維護中',
        '2001' => '無效的參數',
        '2002' => '解密失敗',
        '3005' => '餘額不足',
        '3006' => '找不到交易結果',
        '3008' => '此玩家帳戶不存在',
        '3010' => '此玩家帳戶已存在',
        '3011' => '系統商權限不足',
        '3012' => '遊戲權限不足',
        '3014' => '重複的TransactionID',
        '3015' => '時間不在允許的範圍內',
        '3016' => '拒絕提點，玩家正在遊戲中',
    ];

    public function __construct(array $config)
    {
        $this->config = new RSGConfigConstract();

        $this->config->apiUrl       = $config['apiUrl'];
        $this->config->DesKey       = $config['DesKey'];
        $this->config->DesIv        = $config['DesIv'];
        $this->config->systemCode   = $config['systemCode'];
        $this->config->webId        = $config['webId'];
        $this->config->currency     = $config['currency'];
        $this->config->clientID     = $config['clientID'];
        $this->config->clientSecret = $config['clientSecret'];
        $this->config->lang         = $config['lang'];

        $this->config->timestamp = time();
    }

    /**
     * 獲取遊戲列表.
     *
     * @return void
     */
    public function getGameList()
    {
        $apiUrl = $this->config->apiUrl.'/WithBalance/Game/GameList';

        $params = [
            'SystemCode' => $this->config->systemCode,
        ];

        $result = $this->doSendProcess(null, $apiUrl, $params);

        if (! empty($this->errorMessage[$result->ErrorCode]) && $result->ErrorCode != 0) {
            throw new GameListException(get_class($this), 'error:'.$this->errorMessage[$result->ErrorCode].'! error code : '.$result->ErrorCode, $this->errorMessage[$result->ErrorCode]);
        }

        return $result;
    }

    /**
     * 建立會員
     *
     * @return void
     */
    public function createMember(MemberParameter $member)
    {
        $apiUrl = $this->config->apiUrl.'/WithBalance/Player/CreatePlayer';

        $params = [
            'SystemCode' => $this->config->systemCode,
            'WebId'      => $this->config->webId,
            'UserId'     => $member->playerId,
            'Currency'   => $this->config->currency,
        ];

        $memberFeedback = new MemberFeedback();

        $result = $this->doSendProcess($memberFeedback, $apiUrl, $params);

        if (! empty($this->errorMessage[$result->ErrorCode]) && $result->ErrorCode != 0) {
            throw new CreateMemberException(get_class($this), 'create member error! error code : '.$result->ErrorCode, $this->errorMessage[$result->ErrorCode]);
        }

        return $memberFeedback;
    }

    /**
     * 存款.
     *
     * @return void
     */
    public function deposit(TransferParameter $transfer)
    {
        $apiUrl = $this->config->apiUrl.'/WithBalance/Player/Deposit';

        $params = [
            'SystemCode'    => $this->config->systemCode,
            'WebId'         => $this->config->webId,
            'UserId'        => $transfer->member->playerId,
            'Currency'      => $this->config->currency,
            'TransactionID' => substr($transfer->member->playerId, 0, 10).time(),
            'Balance'       => $transfer->amount,
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($transferFeedback, $apiUrl, $params);

        if ($result->ErrorCode != 0) {
            throw new TransferException(
                get_class($this),
                $this->errorMessage[$result->ErrorCode] ?? $result->ErrorCode,
                $this->errorMessage[$result->ErrorCode] ?? $result->ErrorCode);
        }

        $transferFeedback->balance      = $result->Data->CurrentPlayerBalance;
        $transferFeedback->remote_payno = $result->Data->TransactionID;

        return $transferFeedback;
    }

    /**
     * 提款.
     *
     * @return void
     */
    public function withdraw(TransferParameter $transfer)
    {
        $apiUrl = $this->config->apiUrl.'/WithBalance/Player/Withdraw';

        $params = [
            'SystemCode'    => $this->config->systemCode,
            'WebId'         => $this->config->webId,
            'UserId'        => $transfer->member->playerId,
            'Currency'      => $this->config->currency,
            'TransactionID' => substr($transfer->member->playerId, 0, 10).time(),
            'Balance'       => $transfer->amount,
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($transferFeedback, $apiUrl, $params);

        if ($result->ErrorCode != 0) {
            throw new TransferException(
                get_class($this),
                $this->errorMessage[$result->ErrorCode] ?? $result->ErrorCode,
                $this->errorMessage[$result->ErrorCode] ?? $result->ErrorCode);
        }

        $transferFeedback->balance      = $result->Data->CurrentPlayerBalance;
        $transferFeedback->remote_payno = $result->Data->TransactionID;

        return $transferFeedback;
    }

    /**
     * 會員登入（取得遊戲路徑）.
     *
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $apiUrl = $this->config->apiUrl.'/WithBalance/Player/GetURLToken';

        $params = [
            'SystemCode'  => $this->config->systemCode,
            'WebId'       => $this->config->webId,
            'UserId'      => $launchGameParams->member->playerId,
            'UserName'    => $launchGameParams->member->playerId,
            'GameId'      => (int) $launchGameParams->gameId,
            'Currency'    => $this->config->currency,
            'Language'    => $this->config->lang,
            'ExitAction'  => '',
        ];

        $launchGameFeedback = new LaunchGameFeedback();

        $result = $this->doSendProcess($launchGameFeedback, $apiUrl, $params);

        if (! empty($this->errorMessage[$result->ErrorCode]) && $result->ErrorCode != 0) {
            throw new LaunchGameException(get_class($this), 'launchGame error! error code : '.$result->ErrorCode, $this->errorMessage[$result->ErrorCode]);
        }

        $launchGameFeedback->gameUrl       = $result->Data->URL;
        $launchGameFeedback->mobileGameUrl = $result->Data->URL;

        return $launchGameFeedback;
    }

    /**
     * 取得會員餘額.
     *
     * @return void
     */
    public function getBalance(MemberParameter $member)
    {
        $apiUrl = $this->config->apiUrl.'/WithBalance/Player/GetBalance';

        $params = [
            'SystemCode' => $this->config->systemCode,
            'WebId'      => $this->config->webId,
            'UserId'     => $member->playerId,
            'Currency'   => $this->config->currency,
        ];

        $balanceFeedback = new BalanceFeedback();

        $result = $this->doSendProcess($balanceFeedback, $apiUrl, $params);

        if (! empty($this->errorMessage[$result->ErrorCode]) && $result->ErrorCode != 0) {
            throw new BalanceException(get_class($this), 'get balance error! error code : '.$result->ErrorCode, $this->errorMessage[$result->ErrorCode]);
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
        $apiUrl = $this->config->apiUrl.'/WithBalance/Report/GetGameMinReport';

        // 注意時間必須是三分鐘前的世界
        $format  = 'Y-m-d H:i';
        $startAt = Carbon::parse($srp->endAt)->subMinutes(13)->format($format);
        $endAt   = Carbon::parse($srp->endAt)->subMinutes(4)->format($format);

        $params = [
            'SystemCode' => $this->config->systemCode,
            'WebId'      => $this->config->webId,
            'GameType'   => 2,
            'TimeStart'  => $startAt,
            'TimeEnd'    => $endAt,
        ];

        // 對捕魚機
        $fishData = $this->sendSync($apiUrl, $params, $endAt);

        // 對老虎機
        $params['GameType'] = 1;
        $slotData           = $this->sendSync($apiUrl, $params, $endAt);

        return $callback(array_merge($fishData, $slotData));
    }

    private function sendSync($apiUrl, $params, $endAt)
    {
        $result = $this->doSendProcess(null, $apiUrl, $params);

        if (! empty($this->errorMessage[$result->ErrorCode]) && $result->ErrorCode != 0) {
            throw new SyncException(get_class($this), 'syncReport error! error code : '.$result->ErrorCode, $this->errorMessage[$result->ErrorCode]);
        }

        $data = [];
        foreach ($result->Data->GameReport as $row) {
            $data[] = $this->makeSyncCallBackParameter($row, $endAt);
        }

        return $data;
    }

    public function encrypt($data)
    {
        $result = openssl_encrypt(json_encode($data), 'DES-CBC', $this->config->DesKey, OPENSSL_RAW_DATA, $this->config->DesIv);

        if ($result === false) {
            throw new AesException(get_class($this), 'error on AES encrypt !', json_encode($data));
        }

        return base64_encode($result);
    }

    public function decrypt($data)
    {
        $encrypted = base64_decode($data);

        $decode = openssl_decrypt($encrypted, 'DES-CBC', $this->config->DesKey, OPENSSL_RAW_DATA, $this->config->DesIv);

        if ($decode === false) {
            throw new AesException(get_class($this), 'error on AES decode !', $data);
        }

        return $decode;
    }

    private function doSendProcess($feedback, $apiUrl, $params)
    {
        $params = $this->setParams($params);

        $timestamp        = time();
        $this->curlHeader = [
            'Content-Type:application/x-www-form-urlencoded',
            'X-API-ClientID:'.$this->config->clientID,
            'X-API-Timestamp:'.$timestamp,
            'X-API-Signature:'.md5($this->config->clientID.$this->config->clientSecret.$timestamp.$params['Msg']),
        ];

        $response = $this->post($apiUrl, http_build_query($params), false);

        $decode = json_decode($this->decrypt($response));

        return $decode;
    }

    private function setParams($params)
    {
        $params = [
            'Msg' => $this->encrypt($params),
        ];

        return $params;
    }

    private function makeSyncCallBackParameter($row, $endAt)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid         = $row->SequenNumber;
        $callBackParam->username    = $row->UserId;
        $callBackParam->betAmount   = $row->BetSum;
        $callBackParam->validAmount = $row->BetSum;
        $callBackParam->gameCode    = $row->GameId;
        $callBackParam->winAmount   = $row->WinSum;
        $callBackParam->prize       = $row->JackpotWinSum;
        $callBackParam->betAt       = $endAt;
        $callBackParam->reportAt    = $endAt;
        $callBackParam->status      = Report::STATUS_COMPLETED;

        return $callBackParam;
    }
}
