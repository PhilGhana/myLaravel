<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Game;
use GameProvider\Operator\BaseApi;

use GameProvider\Operator\Multi\Config\TPGConfigConstract;

use GameProvider\Operator\Multi\BaseMultiWalletInterface;

use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\TransferParameter;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;

use GameProvider\Exceptions\AesException;
use GameProvider\Exceptions\LoginException;
use GameProvider\Exceptions\GameListException;
use GameProvider\Exceptions\JSONException;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\CreateMemberException;
use GameProvider\Exceptions\LaunchGameException;
use GameProvider\Exceptions\SyncException;
use GameProvider\Exceptions\TransferException;

use GameProvider\Operator\Feedback\MemberFeedback;
use GameProvider\Operator\Feedback\TransferFeedback;
use GameProvider\Operator\Feedback\BalanceFeedback;
use GameProvider\Operator\Feedback\LaunchGameFeedback;

use App\Models\Report;
use Carbon\Carbon;

class TPG extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $curlHeader = ['Content-Type:application/json;charset=utf-8'];

    private $reportLimit = 100; // 1 ~ 100

    function __construct(array $config)
    {
        $this->config = new TPGConfigConstract();

        $this->config->apiUrl = $config['apiUrl'];
        $this->config->gameUrl = $config['gameUrl'];
        $this->config->serverUrl = $config['serverUrl'];
        $this->config->OperatorId = $config['OperatorId'];
        $this->config->currency = $config['currency'];
        $this->config->lang = $config['lang'];
    }

    public function getGameList()
    {
    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $tokenResult = $this->getToken($member);
        
        if ($tokenResult->status !== 1 || !$tokenResult->message) {
            throw new CreateMemberException(get_class($this), 'create member error! get token fail! error code : ' . $tokenResult->status);
        }

        $memberFeedback = new MemberFeedback();

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
        $params = [
            'operatorId' => $this->config->OperatorId,
            'playerName' => $member->playerId
        ];

        $url = 'game/GetPlayerGameBalance';

        $balanceFeedback = new BalanceFeedback();

        $result = $this->doSendProcess($params, $url, false);

        if ($result->status === 1) {
            $balanceFeedback->balance = $result->totalBalance;
            return $balanceFeedback;
        }

        throw new BalanceException(get_class($this), 'get balance error! error code : ' . $result->status);
    }

    /**
     * 會員登入（取得遊戲路徑）
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */

    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $tokenResult = $this->getToken($launchGameParams->member);
        
        if ($tokenResult->status !== 1 || !$tokenResult->message) {
            throw new LaunchGameException(get_class($this), 'launch game error! get token fail! error code : ' . $tokenResult->status);
        }

        $token = $tokenResult->message;

        $gameIdSplit = explode('_', $launchGameParams->gameId);
        $gameType = $gameIdSplit[0];
        $gameCode = $gameIdSplit[1];

        $playMode = 4;
        if ($launchGameParams->fun) {
            $playMode = 1; // 試玩
        }

        $launchGameFeedback = new LaunchGameFeedback();
        
        $launchGameFeedback->gameUrl = $this->config->gameUrl . 'game/direct2Game/' . $this->config->OperatorId . '/' . $gameType . '/' . $gameCode . '/' . $playMode . '/' . $token . '?lang=' . $this->config->lang . '&backUrl=';
        
        return $launchGameFeedback;
    }


    /**
     * 存款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $url = 'game/FundTransfer';

        $payno =  substr($transfer->member->playerId, 0, 10) . time();

        $params = [
            'operatorId'    => $this->config->OperatorId,
            'username'      => $transfer->member->playerId,
            'displayName'   => $transfer->member->playerId,
            'currency'      => $this->config->currency,
            'amount'        => $transfer->amount,
            'transferType'  => '1',
            'transactionId' => $payno,
            'clientIp'      => '127.0.0.1'
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($params, $url, true);

        if($result->status === 1)
        {
            $transferFeedback->remote_payno = $payno;
            return $transferFeedback;
        }

        throw new TransferException(get_class($this), 'deposit error! error code : ' . $result->status);
    }

     /**
     * 提款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $url = 'game/FundTransfer';

        $payno =  substr($transfer->member->playerId, 0, 10) . time();

        $params = [
            'operatorId'    => $this->config->OperatorId,
            'username'      => $transfer->member->playerId,
            'displayName'   => $transfer->member->playerId,
            'currency'      => $this->config->currency,
            'amount'        => $transfer->amount,
            'transferType'  => '2',
            'transactionId' => $payno,
            'clientIp'      => '127.0.0.1'
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($params, $url, true);

        if($result->status === 1)
        {
            $transferFeedback->remote_payno = $payno;
            return $transferFeedback;
        }
        throw new TransferException(get_class($this), 'withdraw error! error code : ' . $result->status);
    }

     /**
     * 同步注單(取回時間區段的所有注單)
     *
     * 限制10秒存取一次
     * 查詢區間限制為10分鐘
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {

        // 單次限拉10分鐘
        $format = 'Y-m-d H:i:s';
        $startAt = Carbon::parse($srp->startAt)->format($format);
        $endAt = Carbon::parse($srp->endAt)->format($format);
        $startAt = str_replace(' ', 'T', $startAt);
        $endAt = str_replace(' ', 'T', $endAt);
        $params = [
            'operatorId'    => $this->config->OperatorId,
            'from'          => $startAt,
            'to'            => $endAt,
            'limit'         => $this->reportLimit,
            'offset'        => 0
        ];
        return $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
        $url = 'NewGetBatchTxnHistory';
        $result = $this->doSendProcess($params, $url, false);

        $data = [];

        if ($result->status === 63) { // 沒有交易資料
            return $data;
        }

        if ($result->status !== 1) {
            throw new SyncException(get_class($this), 'sync report error! error code : ' . $result->status . 'params : '.json_encode($params).' result :'.json_encode($result));
        }

        $rows = $result->data;


        foreach($rows as $row)
        {
            $data[] = $this->makeSyncCallBackParameter($row);
        }

        if ($result->totalRows >= $this->reportLimit) {
            $params['offset'] = $params['offset'] + $this->reportLimit;
            $data = array_merge($data, $this->doSyncReport($params));
        }

        return $data;

    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid = $row->transaction_id;
        $callBackParam->username = $row->username;
        $callBackParam->gameCode = $row->game_type . '_' . $row->game_theme;

        $callBackParam->reportAt = $row->created_at; // 結算時間
        $callBackParam->betAt = $row->created_at;


        $callBackParam->status = Report::STATUS_COMPLETED;
        if ($row->completed == 0) {
            $callBackParam->status = Report::STATUS_CANCEL;
        }

        $transactionDetail = json_decode($row->transaction_detail);

        switch($row->game_type) {
            case 1:   //老虎机
                $callBackParam->betAmount = $transactionDetail->total_deduct_amount;
                $callBackParam->validAmount = $transactionDetail->total_deduct_amount;
                $callBackParam->winAmount = $transactionDetail->total_payout_amount;
                break;
            case 2  : //水果机游戏
                $callBackParam->betAmount = $transactionDetail->total_bet;
                $callBackParam->validAmount = $transactionDetail->total_bet;
                $callBackParam->winAmount = $transactionDetail->total_payout;
                break;
            case 3  : //桌面游戏
                $callBackParam->betAmount = $transactionDetail->total_bet_amount;
                $callBackParam->validAmount = $transactionDetail->total_bet_amount;
                $callBackParam->winAmount = $transactionDetail->total_payout_amount;
                break;
            case 5  : //基诺游戏
                $callBackParam->betAmount = $transactionDetail->bet_amount;
                $callBackParam->validAmount = $transactionDetail->bet_amount;
                $callBackParam->winAmount = $transactionDetail->payout_amount;
                break;
            case 6  : //休闲游戏
                $callBackParam->betAmount = $transactionDetail->bet_amount;
                $callBackParam->validAmount = $transactionDetail->bet_amount;
                $callBackParam->winAmount = $transactionDetail->payout_amount;
                break;
            case 10 : //钓鱼游戏
                $callBackParam->betAmount = $transactionDetail->bet_amount;
                $callBackParam->validAmount = $transactionDetail->bet_amount;
                $callBackParam->winAmount = $transactionDetail->payout_amount;
                break;
            default:
                //
                break;
        }

        return $callBackParam;
    }

    /**
     * 取得會員token
     */
    private function getToken(MemberParameter $member) {

        $url = 'game/GetGameToken';

        $params = [
            'operatorId'    => $this->config->OperatorId,
            'playerName'    => $member->playerId,
            'displayName'   => $member->playerId,
            // 'displayName'   => $member->username,
            'currency'      => $this->config->currency,
            'loginIp'       => '127.0.0.1'
        ];
        $result = $this->doSendProcess($params, $url, false);
        return $result;
    }

    private function doSendProcess($params, $apiUrl, $isPost = true)
    {
        $fullParams = json_encode($params);

        $url = $this->config->apiUrl;

        if ($isPost) {
            $this->curlHeader = array(
                "enctype=application/x-www-form-urlencoded"
            );
            // $response = $this->post($url .$apiUrl, $fullParams, false);
            $response = $this->post($url .$apiUrl, http_build_query($params), false);

        } else {
            $get_params = http_build_query($params);
            $response = $this->get($url .$apiUrl . '?' . $get_params, $fullParams, false);
        }

        $result = json_decode($response);

        // 如果解不開，就直接把錯誤丟回去
        if($result === null)
        {
            throw new JSONException(get_class($this), 'error on JSON decode !', $result);
        }

        return $result;
    }

}
