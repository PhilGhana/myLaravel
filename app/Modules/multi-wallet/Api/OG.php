<?php

namespace MultiWallet\Api;

use MultiWallet\Api\Config\OGConfigConstract;

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

class OG extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $token = null;

    //沒有給回傳錯誤訊息格式, 待確認
    protected $errorMessage = [
        'Forbidden' => 'X-Operator Or X-Key 錯誤',
        'invalid token' => '無效 token',
        'invalid paramater' => '錯誤參數設定',
        'forbidden' => '遊戲商錯誤', //訊息重複, 變成小寫, 狀況是否一樣要再確認
        'InternalServerError' => '內部服務器錯誤',
        'Missing parameter' => '數值不可為空',
        'User not found' => '會員不存在',
        'transderId already exists' => '交易單號已經存在',
        'Transfer failed' => '轉帳失敗',
        'Game not found' => '錯誤的遊戲代號',
        'Access denied' => '錯誤的Operator 或 Key',
        'The s date field is required' => '必填入SDate',
        'The e date field is required' => '必填入EDate',
        'The Minimum should be 10 minutes' => '獲取區間只能在10分鐘',
        'The provider field value is not valid' => '遊戲商填入無效值'
    ];

    function __construct(array $config)
    {
        $this->config = new OGConfigConstract;

        $this->config->agid = $config['agid'];
        $this->config->secret = $config['secret'];
    }

    public function login()
    {
        // 不重複登入
        if ($this->token !== null) {
            return $this->token;
        }

        $params = [
            'X-Operator' => $this->config->agid,
            'X-key'      => $this->config->secret
        ];

        array_push($this->curlHeader, $params);

        $result = $this->get('/token', null, false);

        if ($result->status === "success") {
            $this->token = $result->data->token;
            return $this->token;
        }

        throw new LoginException('OG server side login error!');
    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $token = $this->login();

        array_push($this->curlHeader, 'X-Token:' . $token);

        $params = [
            'username'  => $member->username,
            'country'   => $member->country ?? 'China',
            'fullname'  => $member->nickname,
            'language'  => $member->language ?? 'en',
            'email'     => $member->email,
            'birthdate' => $member->birthdate,

        ];

        $memberFeedback = new MemberFeedback();

        $url = '/register';
        $result = $this->doSendProcess($memberFeedback, $params, $url);

        if ($result instanceof MemberFeedback) {
            return $result;
        }

        if ($this->reponseCode === 200) {
            $memberFeedback->estendParam = $params->username;

            return $memberFeedback;
        }
    }

    /**
     * 取得會員餘額
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        $token = $this->login();

        array_push($this->curlHeader, 'X-Token:' . $token);

        $params = [
            'username' => $member->username,
        ];

        $agid = $this->config->agid;

        $url = '/game-providers/' . $agid . '/balance';

        $balanceFeedback = new BalanceFeedback();

        $response = $this->get($url, $params, false);
        $result = $response;

        if ($result instanceof BalanceFeedback) {
            return $result;
        }

        if ($result->status === 'success') {
            $balanceFeedback->balance = $result->data->balance;

            return $balanceFeedback;
        }
    }

    /**
     * 會員登入（取得遊戲路徑）
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */

    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $token = $this->login();

        array_push($this->curlHeader, 'X-Token:' . $token);

        $agid = $this->config->agid;
        $gameCode = $launchGameParams->gameId;

        //取得遊戲金鑰
        $keyUrl = '/game-providers/' . $agid . '/games/' . $gameCode . '/key';

        $keyParams = [
            'username' => $launchGameParams->member->username
        ];

        $keyResult = $this->get($keyUrl, $keyParams, false);

        $key = '';

        $launchGameFeedback = new LaunchGameFeedback();


        if ($keyResult->status === 'success') {
            $key = $keyResult->data->key;

            $gameUrl = '/game-providers/' . $agid . '/play';

            $gameParams = [
                'key' => $key,
                'type' => $launchGameParams->device
            ];

            $gameResponse = $this->get($gameUrl, $gameParams, false);
            $gameResult = json_decode($gameResponse);

            if ($gameResult instanceof LaunchGameFeedback) {
                return $gameResult;
            }

            if ($gameResult->status === 'success') {
                $launchGameFeedback->gameUrl = $gameResult->data->url;
                return $launchGameFeedback;
            }
        }
    }


    /**
     * 存款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $token = $this->login();
        array_push($this->curlHeader, 'X-Token:' . $token);

        $agid = $this->config->agid;

        $url = '/game-providers/' . $agid . '/balance';
        $payno = md5($transfer->member->playerId . time());

        $params = [
            'username'   => $transfer->member->username,
            'balence'    => $transfer->amount,
            'action'     => 'IN',
            'transferId' => $payno
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($transferFeedback, $params, $url);

        if ($result instanceof TransferFeedback) {
            return $result;
        }

        if ($result->status === 'success') {
            $transferFeedback->balance = $result->data->balance;

            return $transferFeedback;
        }
    }

    /**
     * 提款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $token = $this->login();
        array_push($this->curlHeader, 'X-Token:' . $token);

        $agid = $this->config->agid;

        $url = '/game-providers/' . $agid . '/balance';
        $payno = md5($transfer->member->playerId . time());

        $params = [
            'username'   => $transfer->member->username,
            'balence'    => $transfer->amount,
            'action'     => 'OUT',
            'transferId' => $payno
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($transferFeedback, $params, $url);

        if ($result instanceof TransferFeedback) {
            return $result;
        }

        if ($result->status === 'success') {
            $transferFeedback->balance = $result->data->balance;

            return $transferFeedback;
        }
    }

    /**
     * 同步注單(取回時間區段的所有注單)
     *
     * 限制10秒存取一次
     * 查詢區間限制為10分鐘
     * @return void
     */
    public function syncReport(SyncReportParameters $srp, callable $callback)
    {
        $token = $this->login();
        array_push($this->curlHeader, 'X-Token:' . $token);

        $params = [
            'Operator'          => $this->config->agid,
            'Key'               => $this->config->secret,
            'SDate'             => $srp->startAt,
            'EDate'             => $srp->endAt,
            'Provider'          => 'OG',
            'PlayerID'          => null,
            'TransactionNumber' => null,
            'Exact'             => false
        ];

        $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
        $callBackFeedback = new SyncCallBackFeedback();

        $url = '/transaction';
        $result = $this->doSendProcess($callBackFeedback, $params, $url);

        if ($this->reponseCode === 200) {
            $rows = $result;

            $data = [];

            foreach ($rows as $row) {
                $data[] = $this->makeSyncCallBackParameter($row);
            }


            if ($result->totalpage > $params['page']) {
                $params['page'] = $params['page'] + 1;
                $data = array_merge($data, $this->doSyncReport($params));
            }

            return $data;
        }
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid = $row->id;
        $callBackParam->username = $row->membername;
        $callBackParam->betAmount = $row->bettingamount;
        $callBackParam->validAmount = $row->validbet;
        $callBackParam->gameCode = $row->gameid;
        $callBackParam->winAmount = $row->winloseamount;
        $callBackParam->betAt = $row->bettingdate;
        // $callBackParam->reportAt = //未提供
        // $callBackParam->ip = //未提供
        $callBackParam->status = $row->status; //格式是:下注區域^下注金額^輸贏金額

        return $callBackParam;
    }

    private function doSendProcess($feedback, $params, $apiUrl)
    {
        $fullParams = json_encode($params);
        $response = $this->post($apiUrl, $fullParams, false);
        // var_dump($response);

        $result = json_decode($response);

        // 如果解不開，就直接把錯誤丟回去
        if ($response === false || $result === null) {
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
}
