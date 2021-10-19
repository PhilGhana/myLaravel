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
use GameProvider\Operator\Multi\Config\DDFConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;

class DDF extends BaseApi implements BaseMultiWalletInterface
{
    protected $version = 'v2';

    protected $config;

    // 預設錯誤訊息
    protected $errorMessage = [
        '-1'    => 'exception failed',
        '101'   => 'invalid key parameter',
        '102'   => 'authorization failed',
    ];

    public function __construct(array $config)
    {
        $this->config         = new DDFConfigConstract();
        $this->config->apiKey = $config['api_key'];
        $this->config->agent  = $config['agent'];
    }

    /**
     * 獲取遊戲列表.
     *
     * @return void
     */
    public function getGameList()
    {
        $apiUrl = $this->version.'/api/games';
        $params = [
            'key' => $this->getKey(),
        ];

        $result = $this->doSendProcess($apiUrl, $params);
        $code   = strval($result['Code']);

        if ($code === '1') {
            return $result['data'];
        }

        throw new GameListException(get_class($this), 'Error when start getting game list error code : '.$code);
    }

    /**
     * 會員登入（取得遊戲路徑）.
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $gameData = $this->getGameList();
        $gameData = collect($gameData);
        $gameData = $gameData->pluck('game_id'.'platform');

        $platform = $launchGameParams->gameId;
        $apiUrl   = $this->version.'/api/redirect/'.$platform.'/game-url';
        $params   = [
            'key'       => $this->getKey(),
            'game_id'   => $gameData[$platform],
            'account'   => $launchGameParams->member->username,
            'password'  => $launchGameParams->member->password,
        ];

        $errorMsg = [
            '-2'    => 'platform not exists',
            '-3'    => 'invalid account parameter',
            '-4'    => 'invalid password parameter',
            '-5'    => 'invalid game_id parameter',
            '-6'    => 'invalid agent_id parameter',
            '-7'    => 'user not exist',
            '-8'    => 'game maintaining',
            '-9'    => 'password error',
            '-10'   => 'other game member can not find it',
        ];
        $this->errorMessage = array_merge($this->errorMessage, $errorMsg);

        $launchGameFeedback = new LaunchGameFeedback();
        $result             = $this->doSendProcess($apiUrl, $params);
        $code               = strval($result['Code']);

        if ($code === '1') {
            $data                              = $result['Data'];
            $launchGameFeedback->gameUrl       = $data->url;
            $launchGameFeedback->mobileGameUrl = $data->url;
            $launchGameFeedback->response_code = $this->reponseCode;

            return $launchGameFeedback;
        }

        // 發生錯誤
        throw new LaunchGameException(get_class($this), 'launch game error! error code : '.$code, $this->errorMessage[$code]);
    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $apiUrl = $this->version.'/api/user/create';
        $params = [
            'key'       => $this->getKey(),
            'role'      => 'member',
            'agent'     => $this->config->agent,
            'account'   => $member->username,
            'password'  => $member->password,
            'nickname'  => urlencode($member->nickname),
        ];

        $errorMsg = [
            '-2' => 'invalid role parameter',
            '-3' => 'invalid agent parameter',
            '-4' => 'invalid account parameter',
            '-5' => 'invalid password parameter',
            '-6' => 'invalid nickname parameter',
            '-7' => 'the specified account already exists',
            '-8' => 'agent not exist',
            '-9' => 'create failed',
        ];
        $this->errorMessage = array_merge($this->errorMessage, $errorMsg);

        $memberFeedback = new MemberFeedback();
        $result         = $this->doSendProcess($apiUrl, $params);
        $code           = strval($result['Code']);

        if ($code === '1') {
            $memberFeedback->extendParam = $result['Data']['User'];

            return $memberFeedback;
        }

        throw new CreateMemberException(get_class($this), 'create member error! error code : '.$code, $this->errorMessage[$code]);
    }

    /**
     * 存款.
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $apiUrl = $this->version.'/api/transfer';
        $payno  = md5($transfer->member->playerId.time());
        $params = [
            'key'       => $this->getKey(),
            'user'      => $transfer->member->username,
            'trans_id'  => $payno,
            'amount'    => $transfer->amount,
        ];

        switch (__FUNCTION__) {
            case 'deposit':
                $params['type'] = 3;
                break;

            case 'withdraw':
                $params['type'] = 4;
                break;
        }

        $errorMsg = [
            '2'     => '會員餘額不足',
            '3'     => '上層餘額不足',
            '4'     => '交易失敗',
            '-2'    => 'invalid user parameter',
            '-3'    => 'invalid trans_id parameter',
            '-4'    => 'invalid amount parameter',
            '-5'    => 'invalid type parameter',
            '-6'    => 'the specified transfer_id already exists',
            '-7'    => 'user not exist',
            '-8'    => 'permission denied',
            '-9'    => 'user in summarize please wait',
            '-10'   => 'user need offline',
            '-11'   => 'transfer failed',
        ];
        $this->errorMessage = array_merge($this->errorMessage, $errorMsg);

        $transferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess($apiUrl, $params);
        $code             = strval($result['Code']);

        if ($code === '1') {
            if ($result['Data']['Status'] == '1') {
                $moneyData                       = $result['Data'];
                $transferFeedback->balance       = null;
                $transferFeedback->remote_payno  = $moneyData->TransId;
                $transferFeedback->response_code = $this->reponseCode;

                return $transferFeedback;
            }

            // 發生錯誤
            throw new TransferException(get_class($this), 'deposit error! error code : '.$result['Data']['Status'], $this->errorMessage[$result['Data']['Status']]);
        }

        // 發生錯誤
        throw new TransferException(get_class($this), 'deposit error! error code : '.$code, $this->errorMessage[$code]);
    }

    /**
     * 提款.
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $apiUrl = $this->version.'/api/transfer';
        $payno  = md5($transfer->member->playerId.time());
        $params = [
            'key'       => $this->getKey(),
            'user'      => $transfer->member->username,
            'trans_id'  => $payno,
            'amount'    => $transfer->amount,
        ];

        switch (__FUNCTION__) {
            case 'deposit':
                $params['type'] = 3;
                break;

            case 'withdraw':
                $params['type'] = 4;
                break;
        }

        $errorMsg = [
            '2'     => '會員餘額不足',
            '3'     => '上層餘額不足',
            '4'     => '交易失敗',
            '-2'    => 'invalid user parameter',
            '-3'    => 'invalid trans_id parameter',
            '-4'    => 'invalid amount parameter',
            '-5'    => 'invalid type parameter',
            '-6'    => 'the specified transfer_id already exists',
            '-7'    => 'user not exist',
            '-8'    => 'permission denied',
            '-9'    => 'user in summarize please wait',
            '-10'   => 'user need offline',
            '-11'   => 'transfer failed',
        ];
        $this->errorMessage = array_merge($this->errorMessage, $errorMsg);

        $transferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess($apiUrl, $params);
        $code             = strval($result['Code']);

        if ($code === '1') {
            if ($result['Data']['Status'] == '1') {
                $moneyData                       = $result['Data'];
                $transferFeedback->balance       = null;
                $transferFeedback->remote_payno  = $moneyData->TransId;
                $transferFeedback->response_code = $this->reponseCode;

                return $transferFeedback;
            }

            // 發生錯誤
            throw new TransferException(get_class($this), 'withdraw error! error code : '.$result['Data']['Status'], $this->errorMessage[$result['Data']['Status']]);
        }

        // 發生錯誤
        throw new TransferException(get_class($this), 'withdraw error! error code : '.$code, $this->errorMessage[$code]);
    }

    /**
     * 取得會員餘額.
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        $apiUrl = $this->version.'/api/user/real';
        $params = [
            'key'   => $this->getKey(),
            'user'  => $member->username,
        ];

        $errorMsg = [
            '-2'    => 'invalid user parameter',
            '-3'    => 'user not exist',
            '-4'    => 'user permission denied',
            '-5'    => 'permission denied',
            '-6'    => 'sync user credit failed',
        ];
        $this->errorMessage = array_merge($this->errorMessage, $errorMsg);

        $balanceFeedback = new BalanceFeedback();
        $result          = $this->doSendProcess($apiUrl, $params);
        $code            = strval($result['Code']);

        if ($code === '1') {
            $balanceFeedback->balance       = $result['Data']['Credit'];
            $balanceFeedback->response_code = $this->reponseCode;

            return $balanceFeedback;
        }

        // 發生錯誤
        throw new BalanceException(get_class($this), 'get balance error! error code : '.$code, $this->errorMessage[$code]);
    }

    /**
     * 查詢注單歷程 by Time.
     *
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $params = [
            'key'           => $this->getKey(),
            'start_time'    => Carbon::parse($srp->startAt)->timestamp,
            'end_time'      => Carbon::parse($srp->endAt)->timestamp,
            'page'          => 1,
            'limit'         => 1000,
        ];
        $callback($this->doSyncReport($params));
    }

    /**
     * 取得驗證 KEY.
     */
    private function getKey()
    {
        $now     = date('His');
        $key_md5 = md5($this->config->apiKey.$now);

        return $this->config->agent.$key_md5.$now;
    }

    private function doSendProcess($apiUrl, $params)
    {
        $response = $this->get($apiUrl, $params);

        if (! is_null($response)) {
            $result = $response['body'];

            return $result;
        }

        // 發生錯誤
        throw new JSONException(get_class($this), 'error on JSON decode !', $response);
    }

    private function doSyncReport($params)
    {
        $errorMsg = [
            '-2'    => 'bet not exist',
            '-3'    => 'invalid start_time parameter',
            '-4'    => 'invalid end_time parameter',
            '-5'    => 'invalid page parameter',
            '-6'    => 'invalid limit parameter',
            '-7'    => 'invalid date range selection',
            '-8'    => 'query time interval is too large',
            '-9'    => 'invalid platform parameter',
            '-10'   => 'this platform not supply list',
        ];
        $this->errorMessage = array_merge($this->errorMessage, $errorMsg);

        $apiUrl = $this->version.'/api/bet/record/time';
        $result = $this->doSendProcess($apiUrl, $params);
        $code   = strval($result['Code']);

        if ($code === '1') {
            $rows = $result['Data'];
            $data = [];

            foreach ($rows as $row) {
                $data[] = $this->makeSyncCallBackParameter($row);
            }

            if (count($rows) > $params['page']) {
                $params['page'] = $params['page'] + 1;
                $data           = array_merge($data, $this->doSyncReport($params));
            }

            return $data;
        }

        // 發生錯誤
        throw new SyncException(get_class($this), 'sync error! error code : '.$code, $this->errorMessage[$code]);
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid         = $row['BetId'];
        $callBackParam->username    = $row['User'];
        $callBackParam->betAmount   = $row['BetAmount'];
        $callBackParam->validAmount = $row['BetValid'];
        $callBackParam->betAt       = $row['BetAt'];
        $callBackParam->reportAt    = $row['BetAt'];
        $callBackParam->content     = $row['GameRecord'];
        $callBackParam->round       = $row['GameId'];
        $callBackParam->table       = $row['TableId'];
        // game_id 預設為 1
        $callBackParam->gameCode = '1';

        switch ($row['Status']) {
            // 下注中
            case '00':
            case '01':
            case '05':
                $callBackParam->status = Report::STATUS_BETTING;
                break;

            // 已開獎
            case '02':
            case '06':
                $callBackParam->status = Report::STATUS_SETTLE;
                break;

            // 已結算
            case '07':
                $callBackParam->status = Report::STATUS_COMPLETED;
                break;

            // 已取消
            case '03':
            case '08':
                $callBackParam->status = Report::STATUS_CANCEL;
                break;

            // 注單錯誤
            default:
                $callBackParam->status = Report::STATUS_ERROR;
                break;
        }

        return $callBackParam;
    }
}
