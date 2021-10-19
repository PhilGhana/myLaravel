<?php

namespace MultiWallet\Api;

use MultiWallet\Api\Config\IBOConfigConstract;

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

class IBO extends BaseApi implements BaseMultiWalletInterface
{
    protected $version = '0.0.15';

    protected $config;

    protected $token = null;

    protected $errorMessage = [
        '0001' => '參數錯誤',
        '0002' => 'token 驗證錯誤',
        '0003' => '查無資料',
        '0004' => '信用額度用戶不支持存提款功能',
        '0005' => '上層餘額不足，無法存入',
        '0006' => '餘額不足，無法提出',
        '0007' => '不可新增會員編號',
        '0008' => '輸入的帳號已經有人使用',
        '0011' => '找不到 AID 的資料物件',
        '0012' => '編解碼發生錯誤',
        '0013' => '編解碼發收錯誤',
        '0014' => '未被定義的方法',
        '0015' => '人數已滿',
        '0016' => '維護中',
        '0017' => '系統最佳化',
        '6666' => '測試使用',
        '9998' => '通訊逾時',
        '9999' => '系統異常',
    ];

    protected $gameCode = 'BA_IBO';

    public function __construct(array $config)
    {
        $this->config = new IBOConfigConstract();
        $this->config->apiUrl    = $config['apiUrl'];
        $this->config->username  = $config['username'];
        $this->config->password  = $config['password'];
        $this->config->secret    = $config['secret'];
        $this->config->currency  = $config['currency'];
        $this->config->agid      = $config['agid'];
        $this->config->agname    = $config['agname '];
        $this->config->agentCode = $config['agentCode'];
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
            'memname'   => $member->playerId,
            'password'  => $member->password,
            'currency'  => $this->config->currency,
            'remoteip'  => $_SERVER['SERVER_ADDR'],
            'token'     => $token,
            'timestamp' => $this->getTimestamp()
        ];
        $method = 'CreateMember';

        $memberFeedback = new MemberFeedback();
        $result = $this->doSendProcess($memberFeedback, $params, $method);

        if ($result instanceof MemberFeedback) {
            return $result;
        }

        if ($result->respcode === '0000') {
            $memberFeedback->extendParam = $result->userdata->userid;

            return $memberFeedback;
        }

        $memberFeedback->error_code = $result->respcode;
        $memberFeedback->error_msg = $this->errorMessage[$result->respcode];

        return $memberFeedback;
    }

    /**
     * 存款
     * 必須先查餘額，然後送出交易，再查餘額確認錢是不是真的進去了
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $token = $this->login();
        $payno = md5($transfer->member->playerId . time());
        $params = [
            'memid'         => $transfer->member->playerId,
            'memname'       => $transfer->member->username,
            'agentCode'     => $this->config->agentCode,
            'amount'        => $transfer->amount,
            'remoteip'      => $_SERVER['SERVER_ADDR'],
            'payno'         => $payno,
            'token'         => $token,
            'timestamp'     => $this->getTimestamp(),
            'timeoutset'    => 30
        ];
        $method = 'Deposit';

        $transferFeedback = new TransferFeedback();
        $result = $this->doSendProcess($transferFeedback, $params, $method);

        if ($result instanceof TransferFeedback) {
            return $result;
        }

        // 如果正確
        if ($result->respcode === '0000') {
            $moneyData = $result->moneydata;

            $transferFeedback->balance = $moneyData->gold;
            $transferFeedback->remote_payno = $moneyData->rec_id;
            $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }

        // 發生錯誤
        $transferFeedback->error_code = $result->respcode;
        $transferFeedback->error_msg = $this->errorMessage[$result->respcode];

        return $transferFeedback;
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
        $payno = md5($transfer->member->playerId . time());
        $params = [
            'memname'       => $transfer->member->username,
            'memid'         => $transfer->member->playerId,
            'agentCode'     => $this->config->agentCode,
            'amount'        => $transfer->amount,
            'remoteip'      => $_SERVER['SERVER_ADDR'],
            'payno'         => $payno,
            'token'         => $token,
            'timestamp'     => $this->getTimestamp(),
            'timeoutset'    => 30
        ];
        $method = 'Withdraw';

        $transferFeedback = new TransferFeedback();
        $result = $this->doSendProcess($transferFeedback, $params, $method);

        if ($result instanceof TransferFeedback) {
            return $result;
        }

        if ($result->respcode === '0000') {
            $moneyData = $result->moneydata;

            $transferFeedback->balance = $moneyData->gold;
            $transferFeedback->remote_payno = $moneyData->rec_id;
            $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }

        // 發生錯誤
        $transferFeedback->error_code = $result->respcode;
        $transferFeedback->error_msg = $this->errorMessage[$result->respcode];

        return $transferFeedback;
    }

    /**
     * 登入遊戲
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $token = $this->login();
        $params = [
            'memname'   => $launchGameParams->member->username,
            'memid'     => $launchGameParams->member->playerId,
            'agentCode' => $this->config->agentCode,
            'grp'       => $launchGameParams->group || -1,
            'password'  => $launchGameParams->member->password,
            'langx'     => $this->getLocale(),
            'machine'   => $launchGameParams->device,
            'remoteip'  => request()->ip(),
            'token'     => $token,
            'timestamp' => $this->getTimestamp(),
            'isSSL'     => $launchGameParams->isSSL || 'N'
        ];
        $method = 'LaunchGame';

        $launchGameFeedback = new LaunchGameFeedback();
        $result = $this->doSendProcess($launchGameFeedback, $params, $method);

        if ($result instanceof LaunchGameFeedback) {
            return $result;
        }

        if ($result->respcode === '0000') {
            $launchGameFeedback->gameUrl = $result->launchgameurl;
            $launchGameFeedback->mobileGameUrl = $result->launchgameurl;
            $launchGameFeedback->token = $result->memToken;
            $launchGameFeedback->response_code = $this->reponseCode;

            return $launchGameFeedback;
        }

        // 發生錯誤
        $launchGameFeedback->error_code = $result->respcode;
        $launchGameFeedback->error_msg = $this->errorMessage[$result->respcode];

        return $launchGameFeedback;
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
        $params = [
            'memname'   => $member->username,
            'memid'     => $member->playerId,
            'token'     => $token,
            'timestamp' => $this->getTimestamp()
        ];
        $method = 'chkMemberBalance';

        $balanceFeedback = new BalanceFeedback();
        $result = $this->doSendProcess($balanceFeedback, $params, $method);

        if ($result instanceof BalanceFeedback) {
            return $result;
        }

        if ($result->respcode === '0000') {
            $balanceFeedback->response_code = $this->reponseCode;
            $balanceFeedback->balance = $result->balance;

            return $balanceFeedback;
        }

        // 發生錯誤
        $balanceFeedback->error_code = $result->respcode;
        $balanceFeedback->error_msg = $this->errorMessage[$result->respcode];

        return $balanceFeedback;
    }

    /**
     * 同步全部會員的注單資料 (日期搜索)
     *
     * @return void
     */
    public function syncReport(SyncReportParameters $srp, callable $callback)
    {
        $token = $this->login();
        $dateFormat = 'Y-m-d H:i:s';
        $params = [
            'agname'    => $this->config->agname,
            'agid'      => $this->config->agid,
            'dateStart' => $srp->startAt,
            'dateEnd'   => $srp->endAt,
            'agentCode' => $this->config->agentCode,
            'settle'    => $srp->status,
            'timestamp' => $this->getTimestamp(),
            'page'      => 1,
            'langx'     => $this->getLocale(),
            'token'     => $token
        ];

        $callback($this->doSyncReport($params));
    }

    /**
     * 登入取 TOKEN
     */
    public function login()
    {
        // 如果登過了，不要重複登
        if ($this->token !== null) {
            return $this->token;
        }

        $params = [
            'username'  => $this->config->username,
            'password'  => $this->config->password,
            'agentCode' => $this->config->agentCode,
            'remoteip'  => $_SERVER['SERVER_ADDR'],
            'timestamp' => $this->getTimestamp()
        ];
        $method = 'AGLogin';

        $fullParams = $this->setParams($params, 'AGLogin');
        $response = $this->post($this->config->apiUrl, $fullParams, false);
        $result = json_decode(self::decrypt($response, $this->config->secret));

        if ($result->respcode === '0000') {
            $this->token = $result->token;
            return $this->token;
        }

        throw new LoginException('IBO server side login error!');
    }

    private function doSendProcess($feedback, $params, $method, $version = null)
    {
        $fullParams = $this->setParams($params, $method, $version);
        $response = $this->post($this->config->apiUrl, $fullParams, false);
        $decode = self::decrypt($response, $this->config->secret);
        $result = json_decode($decode);

        // 如果解不開，就直接把錯誤丟回去
        if ($decode === false || $result === null) {
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

    private function setParams($params, $method, $version = null)
    {
        $params = [
            'Request' => self::encrypt($params, $this->config->secret),
            'Method'  => $method,
            'AGID'    => $this->config->agid
        ];

        if ($version !== null){
            $params['Version'] = $version;
        }

        return json_encode($params);
    }

    /**
     * 回傳時間戳記
     *
     * @return string
     */
    private function getTimestamp()
    {
        $tz = -4;
        $now = Carbon::createFromTimeStampUTC($tz);

        return $now->timestamp;
    }

    /**
     * 取得語系
     */
    private function getLocale()
    {
        $langs = [
                'zh-tw',
                'zh-cn',
                'en-us',
                'ko-kr',
                'ja-jp',
            ];
        $lang = (in_array(app()->getLocale(), $langs))?app()->getLocale():'zh-cn';

        return $lang;
    }

    private function doSyncReport($params)
    {
        $callBackFeedback = new SyncCallBackFeedback();
        $result = $this->doSendProcess($callBackFeedback, $params, 'ALLWager');

        if ($result instanceof SyncCallBackFeedback) {
            return $result;
        }

        if ($result->respcode === '0000') {
            $rows = $result->wager_data;
            $data = [];

            foreach ($rows as $row) {
                $data[] = $this->makeSyncCallBackParameter($row);
            }

            if ($result->wager_totalpage > $params['page']) {
                $params['page'] = $params['page'] + 1;
                $data = array_merge($data, $this->doSyncReport($params));
            }

            return $data;
        }

        $callBackFeedback->error_code = $result->respcode;
        $callBackFeedback->error_msg = $this->errorMessage[$result->respcode];

        return $callBackFeedback;
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();
        $callBackParam->mid = $row->id;
        $callBackParam->gameCode = $this->gameCode;
        $callBackParam->username = $row->username;
        $callBackParam->betAmount = $row->gold;
        $callBackParam->betAt = $row->orderdate;
        $callBackParam->reportAt = $row->resultdate;
        $callBackParam->validAmount = $row->vgold;
        $callBackParam->winAmount = $row->win_gold;
        $callBackParam->status = $row->status;
        $callBackParam->table = $row->tbid;
        $callBackParam->round = $row->baid;
        $callBackParam->ip = $row->IP;

        // 沒有注單狀態，無法判斷是否退款
        // $status = [
        //     1 => Report::STATUS_BETTING,
        //     2 => Report::STATUS_CANCEL,
        //     3 => Report::STATUS_SETTLE,
        //     4 => Report::STATUS_COMPLETED
        // ];
        // $callBackParam->status = '';

        return $callBackParam;
    }

    public static function encrypt($data, $key)
    {
        $result = openssl_encrypt(json_encode($data), 'aes-128-ecb', $key, OPENSSL_RAW_DATA);

        return base64_encode($result);
    }

    public static function decrypt($data, $key)
    {
        $encrypted = base64_decode($data);

        return openssl_decrypt($encrypted, 'aes-128-ecb', $key, OPENSSL_RAW_DATA);
    }
}
