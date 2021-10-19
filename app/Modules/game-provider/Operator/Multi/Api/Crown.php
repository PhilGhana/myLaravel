<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Report;
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
use GameProvider\Operator\Multi\Config\CrownConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
// use MultiWallet\Feedback\SyncCallBackFeedback;

use GameProvider\Operator\Params\TransferParameter;

class Crown extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $token = null;

    protected $errorMessage = [
        '0001' => '參數錯誤',
        '0002' => 'token 驗證錯誤',
        '0003' => '查無資料',
        '0004' => '用戶不支持存提款功能(帳戶凍結)',
        '0005' => '上層餘額不足，無法存入',
        '0006' => '餘額不足，無法提出',
        '0007' => '不可新增會員帳號',
        '0008' => '輸入的帳號已經有人使用',
        '0011' => '找不到 AID 的資料物件(營商不存在)',
        '0012' => '编解码發生錯誤',
        '0013' => '编解码發生錯誤',
        '0014' => '未被定義的方法',
        '0015' => '人數已滿',
        '0016' => '維護中',
        '6666' => '測試使用',
        '9999' => '系統異常、未知錯誤',
    ];

    public function __construct(array $config)
    {
        $this->config = new CrownConfigConstract();

        $this->config->apiUrl   = $config['apiUrl'];
        $this->config->username = $config['username'];
        $this->config->password = $config['password'];
        $this->config->secret   = $config['secret'];
        $this->config->agid     = $config['agid'];
    }

    /**
     * 獲取遊戲列表.
     *
     * @return void
     */
    public function getGameList()
    {
        $token = $this->login();

        $result = $this->doGameList($token, 1);

        $gameList = [];

        if ($result->respcode === '0000') {
            $totalPage = $result->totalpage;

            // 如果比一頁多，就繼續問
            if ($totalPage <= 1) {
                return $result->gamelist;
            }

            $gameList = $result->gamelist;

            for ($i=2; $i <= $totalPage; $i++) {
                $result = $this->doGameList($token, $i);

                if ($result->respcode === '0000') {
                    $gameList = array_merge($gameList, $result->gamelist);

                    continue;
                }

                throw new GameListException(get_class($this), 'Error in page : '.$i.' error code : '.$result->respcode);
            }

            return $gameList;
        }

        throw new GameListException(get_class($this), 'Error when start getting game list error code : '.$result->respcode);
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

        $params = [
            'memname'  => $member->playerId,
            'token'    => $token,
            'remoteip' => $_SERVER['SERVER_ADDR'],
        ];

        $memberFeedback = new MemberFeedback();

        $result = $this->doSendProcess($params, 'CreateMember', '0.0.1');

        if ($result->respcode === '0000') {
            $memberFeedback->extendParam = $result->userdata->userid;

            return $memberFeedback;
        }

        throw new CreateMemberException(get_class($this), 'create member error! error code : '.$result->respcode, $this->errorMessage[$result->respcode]);
        // $memberFeedback->error_code = $result->respcode;
        // $memberFeedback->error_msg = $this->errorMessage[$result->respcode];

        // return $memberFeedback;
    }

    /**
     * 存款
     * 必須先查餘額，然後送出交易，再查餘額確認錢是不是真的進去了.
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $token = $this->login();
        $payno = md5($transfer->member->playerId.time());

        $params = [
            'memid'    => $transfer->member->username,
            'memname'  => $transfer->member->playerId,
            'amount'   => $transfer->amount,
            'payno'    => $payno,
            'token'    => $token,
            'remoteip' => $_SERVER['SERVER_ADDR'],
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($params, 'Deposit', '0.0.1');

        // 如果正確
        if ($result->respcode === '0000') {
            $moneyData = $result->moneydata;

            $transferFeedback->balance      = $moneyData->gold;
            $transferFeedback->remote_payno = $moneyData->recid;
            // $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }

        // 發生錯誤
        throw new TransferException(get_class($this), 'deposit error! error code : '.$result->respcode, $this->errorMessage[$result->respcode]);
        // $transferFeedback->error_code = $result->respcode;
        // $transferFeedback->error_msg = $this->errorMessage[$result->respcode];

        // return $transferFeedback;
    }

    /**
     * 提款.
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $token = $this->login();
        $payno = md5($transfer->member->playerId.time());

        $params = [
            'memid'    => $transfer->member->username,
            'memname'  => $transfer->member->playerId,
            'amount'   => $transfer->amount,
            'payno'    => $payno,
            'token'    => $token,
            'remoteip' => $_SERVER['SERVER_ADDR'],
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($params, 'Withdraw', '0.0.1');

        if ($result->respcode === '0000') {
            $moneyData = $result->moneydata;

            $transferFeedback->balance      = $moneyData->gold;
            $transferFeedback->remote_payno = $moneyData->recid;
            // $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }

        // 發生錯誤
        throw new TransferException(get_class($this), 'withdraw error! error code : '.$result->respcode, $this->errorMessage[$result->respcode]);
        // $transferFeedback->error_code = $result->respcode;
        // $transferFeedback->error_msg = $this->errorMessage[$result->respcode];

        // return $transferFeedback;
    }

    /**
     * 會員登入（取得遊戲路徑）.
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $token = $this->login();

        $params = [
            'memid'    => $launchGameParams->member->username,
            'memname'  => $launchGameParams->member->playerId,
            'password' => $launchGameParams->member->password,
            'gameid'   => $launchGameParams->gameId,
            'device'   => $launchGameParams->device,
            'token'    => $token,
            'remoteip' => request()->ip(),
        ];

        $launchGameFeedback = new LaunchGameFeedback();

        $result = $this->doSendProcess($params, 'LaunchGame', '0.0.1');

        if ($result->respcode === '0000') {
            $launchGameFeedback->gameUrl       = $result->launchgameurl;
            $launchGameFeedback->mobileGameUrl = $result->launchgameurl;
            $launchGameFeedback->token         = $result->memToken;
            // $launchGameFeedback->response_code = $this->reponseCode;

            return $launchGameFeedback;
        }

        // 發生錯誤
        throw new LaunchGameException(get_class($this), 'launch game error! error code : '.$result->respcode, $this->errorMessage[$result->respcode]);
        // $launchGameFeedback->error_code = $result->respcode;
        // $launchGameFeedback->error_msg = $this->errorMessage[$result->respcode];

        // return $launchGameFeedback;
    }

    /**
     * 取得會員餘額.
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        $token = $this->login();

        $params = [
            'memid'    => $member->username,
            'memname'  => $member->playerId,
            'token'    => $token,
            'remoteip' => $_SERVER['SERVER_ADDR'],
        ];

        $balanceFeedback = new BalanceFeedback();

        $result = $this->doSendProcess($params, 'ChkMemberBalance', '0.0.1');

        if ($result->respcode === '0000') {
            // $balanceFeedback->response_code = $this->reponseCode;
            $balanceFeedback->balance = $result->balance;

            return $balanceFeedback;
        }

        // 發生錯誤
        throw new BalanceException(get_class($this), 'get balance error! error code : '.$result->respcode, $this->errorMessage[$result->respcode]);
        // $balanceFeedback->error_code = $result->respcode;
        // $balanceFeedback->error_msg = $this->errorMessage[$result->respcode];

        // return $balanceFeedback;
    }

    /**
     * 同步注單(取回時間區段的所有注單).
     *
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $token = $this->login();

        // $dateFormat = 'Y-m-d H:i:s';
        $ip = gethostbyname(gethostname());
        if (isset($_SERVER['SERVER_ADDR'])) {
            $ip = $_SERVER['SERVER_ADDR'];
        }

        $params = [
            'datestart' => $srp->startAt,
            'dateend'   => $srp->endAt,
            'settle'    => $srp->status,
            'token'     => $token,
            'page'      => 1,
            'remoteip'  => $ip,
        ];

        return $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
        // $callBackFeedback = new SyncCallBackFeedback();

        $result = $this->doSendProcess($params, 'ALLWager', '0.0.1');

        if ($result->respcode === '0000') {
            $rows = $result->wagerdata;

            $data = [];

            foreach ($rows as $row) {
                $data[] = $this->makeSyncCallBackParameter($row);
            }

            if ($result->totalpage > $params['page']) {
                $params['page'] = $params['page'] + 1;
                $data           = array_merge($data, $this->doSyncReport($params));
            }

            return $data;
        }

        throw new SyncException(get_class($this), 'sync error! error code : '.$result->respcode, $this->errorMessage[$result->respcode]);
        // $callBackFeedback->error_code = $result->respcode;
        // $callBackFeedback->error_msg = $this->errorMessage[$result->respcode];

        // return $callBackFeedback;
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid         = $row->id;
        $callBackParam->username    = $row->memname;
        $callBackParam->betAmount   = $row->gold;
        $callBackParam->validAmount = $row->vgold;
        $callBackParam->gameCode    = $row->gameid;
        $callBackParam->winAmount   = $row->wingold + $row->gold;
        $callBackParam->betAt       = $row->orderdate;
        $callBackParam->reportAt    = $row->orderdate;
        $callBackParam->ip          = $row->ip;
        $callBackParam->round       = $row->wagerdetail->expectid;
        $callBackParam->content     = $row->wagerdetail->betonname;

        $status = [
            1 => Report::STATUS_BETTING,
            2 => Report::STATUS_CANCEL,
            3 => Report::STATUS_SETTLE,
            4 => Report::STATUS_COMPLETED,
        ];

        $callBackParam->status = $status[$row->wagerdetail->status];

        return $callBackParam;
    }

    public function login()
    {
        // 如果登過了，不要重複登
        if ($this->token !== null) {
            return $this->token;
        }

        $ip = gethostbyname(gethostname());
        if (isset($_SERVER['SERVER_ADDR'])) {
            $ip = $_SERVER['SERVER_ADDR'];
        }

        $params = [
            'username' => $this->config->username,
            'password' => $this->config->password,
            'remoteip' => $ip,
        ];

        $fullParams = $this->setParams($params, 'AGLogin', '0.0.1');

        $response = $this->post($this->config->apiUrl, $fullParams, false);

        $result = json_decode(self::decrypt($response, $this->config->secret));

        if ($result->respcode === '0000') {
            $this->token = $result->token;

            return $this->token;
        }

        throw new LoginException(get_class($this), 'server side login error!');
    }

    private function doSendProcess($params, $method, $version = null)
    {
        $fullParams = $this->setParams($params, $method, $version);

        $response = $this->post($this->config->apiUrl, $fullParams, false);

        $decode = self::decrypt($response, $this->config->secret);

        $result = json_decode($decode);

        // 如果解不開，就直接把錯誤丟回去
        if ($result === null) {
            throw new JSONException(get_class($this), 'error on JSON decode !', $result);
        }

        return $result;
    }

    private function doGameList($token, $page)
    {
        $params = [
            'token'    => $token,
            'page'     => $page,
            'remoteip' => $_SERVER['SERVER_ADDR'],
        ];

        $fullParams = $this->setParams($params, 'GameList');

        $response = $this->post($this->config->apiUrl, $fullParams, false);

        return json_decode(self::decrypt($response, $this->config->secret));
    }

    private function setParams($params, $method, $version = null)
    {
        $params = [
            'Request' => self::encrypt($params, $this->config->secret),
            'Method'  => $method,
            'AGID'    => $this->config->agid,
        ];

        if ($version !== null) {
            $params['Version'] = $version;
        }

        return json_encode($params);
    }

    public static function encrypt($data, $key)
    {
        $result = openssl_encrypt(json_encode($data), 'aes-128-ecb', $key, OPENSSL_RAW_DATA);

        if ($result === false) {
            throw new AesException(get_class($this), 'error on AES encrypt !', json_encode($data));
        }

        return base64_encode($result);
    }

    public static function decrypt($data, $key)
    {
        $encrypted = base64_decode($data);

        $decode = openssl_decrypt($encrypted, 'aes-128-ecb', $key, OPENSSL_RAW_DATA);

        if ($decode === false) {
            throw new AesException(get_class($this), 'error on AES decode !', $data);
        }

        return $decode;
    }
}
