<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Report;
use Carbon\Carbon;
use GameProvider\Exceptions\AesException;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\CreateMemberException;
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
use GameProvider\Operator\Multi\Config\IBOConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;

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

    // protected $gameCode = 'BA_IBO';

    public function __construct(array $config)
    {
        $this->config            = new IBOConfigConstract();
        $this->config->apiUrl    = $config['apiUrl'];
        $this->config->username  = $config['username'];
        $this->config->password  = $config['password'];
        $this->config->secret    = $config['secret'];
        $this->config->currency  = $config['currency'];
        $this->config->agid      = $config['agid'];
        $this->config->agname    = $config['agname '];
        $this->config->agentCode = $config['agentCode'];
        $this->config->language  = $config['language'];
    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $token  = $this->login();
        $params = [
            'memname'   => $member->playerId,
            'password'  => $member->password,
            'currency'  => $this->config->currency,
            'remoteip'  => $_SERVER['SERVER_ADDR'],
            'token'     => $token,
            'timestamp' => $this->getTimestamp(),
        ];
        $method = 'CreateMember';

        $memberFeedback = new MemberFeedback();
        $result         = $this->doSendProcess($params, $method);

        if ($result->respcode === '0000') {
            $memberFeedback->extendParam = $result->userdata->userid;

            return $memberFeedback;
        }

        throw new CreateMemberException(get_class($this), 'create member error! error code : '.$result->respcode, $this->errorMessage[$result->respcode]);
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
        $token  = $this->login();
        $payno  = md5($transfer->member->playerId.time());
        $params = [
            'memid'         => $transfer->member->playerId,
            'memname'       => $transfer->member->username,
            'agentCode'     => $this->config->agentCode,
            'amount'        => $transfer->amount,
            'remoteip'      => $_SERVER['SERVER_ADDR'],
            'payno'         => $payno,
            'token'         => $token,
            'timestamp'     => $this->getTimestamp(),
            'timeoutset'    => 30,
        ];
        $method = 'Deposit';

        $transferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess($params, $method);

        // 如果正確
        if ($result->respcode === '0000') {
            $moneyData = $result->moneydata;

            $transferFeedback->balance       = $moneyData->gold;
            $transferFeedback->remote_payno  = $moneyData->rec_id;
            $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }

        // 發生錯誤
        throw new TransferException(get_class($this), 'deposit error! error code : '.$result->respcode, $this->errorMessage[$result->respcode]);
    }

    /**
     * 提款.
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $token  = $this->login();
        $payno  = md5($transfer->member->playerId.time());
        $params = [
            'memname'       => $transfer->member->username,
            'memid'         => $transfer->member->playerId,
            'agentCode'     => $this->config->agentCode,
            'amount'        => $transfer->amount,
            'remoteip'      => $_SERVER['SERVER_ADDR'],
            'payno'         => $payno,
            'token'         => $token,
            'timestamp'     => $this->getTimestamp(),
            'timeoutset'    => 30,
        ];
        $method = 'Withdraw';

        $transferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess($params, $method);

        if ($result->respcode === '0000') {
            $moneyData = $result->moneydata;

            $transferFeedback->balance       = $moneyData->gold;
            $transferFeedback->remote_payno  = $moneyData->rec_id;
            $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }

        // 發生錯誤
        throw new TransferException(get_class($this), 'withdraw error! error code : '.$result->respcode, $this->errorMessage[$result->respcode]);
    }

    /**
     * 登入遊戲.
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $token  = $this->login();
        $params = [
            'memname'   => $launchGameParams->member->username,
            'memid'     => $launchGameParams->member->playerId,
            'agentCode' => $this->config->agentCode,
            'grp'       => $launchGameParams->group || -1,
            'password'  => $launchGameParams->member->password,
            'langx'     => $this->getLocale($launchGameParams->member->language),
            'machine'   => $launchGameParams->device,
            'remoteip'  => request()->ip(),
            'token'     => $token,
            'timestamp' => $this->getTimestamp(),
            'isSSL'     => $launchGameParams->isSSL || 'N',
        ];
        $method = 'LaunchGame';

        $launchGameFeedback = new LaunchGameFeedback();
        $result             = $this->doSendProcess($params, $method);

        if ($result->respcode === '0000') {
            $launchGameFeedback->gameUrl       = $result->launchgameurl;
            $launchGameFeedback->mobileGameUrl = $result->launchgameurl;
            $launchGameFeedback->token         = $result->memToken;
            $launchGameFeedback->response_code = $this->reponseCode;

            return $launchGameFeedback;
        }

        // 發生錯誤
        throw new LaunchGameException(get_class($this), 'launch game error! error code : '.$result->respcode, $this->errorMessage[$result->respcode]);
    }

    /**
     * 取得會員餘額.
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        $token  = $this->login();
        $params = [
            'memname'   => $member->username,
            'memid'     => $member->playerId,
            'token'     => $token,
            'timestamp' => $this->getTimestamp(),
        ];
        $method = 'chkMemberBalance';

        $balanceFeedback = new BalanceFeedback();
        $result          = $this->doSendProcess($params, $method);

        if ($result->respcode === '0000') {
            $balanceFeedback->response_code = $this->reponseCode;
            $balanceFeedback->balance       = $result->balance;

            return $balanceFeedback;
        }

        // 發生錯誤
        throw new BalanceException(get_class($this), 'get balance error! error code : '.$result->respcode, $this->errorMessage[$result->respcode]);
    }

    /**
     * 同步全部會員的注單資料 (日期搜索).
     *
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $token = $this->login();

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
            'token'     => $token,
        ];

        $callback($this->doSyncReport($params));
    }

    /**
     * 登入取 TOKEN.
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
            'timestamp' => $this->getTimestamp(),
        ];

        $fullParams = $this->setParams($params, 'AGLogin');
        $response   = $this->post($this->config->apiUrl, $fullParams, false);
        $result     = json_decode(self::decrypt($response, $this->config->secret));

        if ($result->respcode === '0000') {
            $this->token = $result->token;

            return $this->token;
        }

        throw new LoginException(get_class($this), 'server side login error!');
    }

    /**
     * 回傳時間戳記.
     *
     * @return string
     */
    private function getTimestamp()
    {
        $tz  = -4;
        $now = Carbon::createFromTimeStampUTC($tz);

        return $now->timestamp;
    }

    /**
     * 取得語系.
     *
     * @param string $memberLang
     * @return string
     */
    private function getLocale($memberLang = null)
    {
        $langs = [
                'zh-Hant'   => 'zh-tw',
                'zh-Hans'   => 'zh-cn',
                'en'        => 'en-us',
                'ko'        => 'ko-kr',
                'ja'        => 'ja-jp',
            ];

        if (! is_null($memberLang) && array_key_exists($memberLang, $langs)) {
            return $langs[$memberLang];
        }

        return $langs[$this->config->language];
    }

    private function doSyncReport($params)
    {
        $result = $this->doSendProcess($params, 'ALLWager');

        if ($result->respcode === '0000') {
            $rows = $result->wager_data;
            $data = [];

            foreach ($rows as $row) {
                $data[] = $this->makeSyncCallBackParameter($row);
            }

            if ($result->wager_totalpage > $params['page']) {
                $params['page'] = $params['page'] + 1;
                $data           = array_merge($data, $this->doSyncReport($params));
            }

            return $data;
        }

        throw new SyncException(get_class($this), 'sync error! error code : '.$result->respcode, $this->errorMessage[$result->respcode]);
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam      = new SyncCallBackParameter();
        $callBackParam->mid = $row->id;
        // $callBackParam->gameCode = $this->gameCode;
        $callBackParam->gameCode    = $row->gtype;
        $callBackParam->username    = $row->username;
        $callBackParam->betAmount   = $row->gold;
        $callBackParam->betAt       = $row->orderdate;
        $callBackParam->reportAt    = $row->orderdate;
        $callBackParam->validAmount = $row->vgold;
        $callBackParam->winAmount   = $row->win_gold;
        // $callBackParam->status = $row->status;
        // 對方根本沒改status，先給注單完成
        $callBackParam->status = Report::STATUS_COMPLETED;
        $callBackParam->table  = $row->tbid;
        $callBackParam->round  = $row->baid;
        $callBackParam->ip     = $row->IP;

        $callBackParam->content = $row->tname;

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

    private function doSendProcess($params, $method)
    {
        $fullParams = $this->setParams($params, $method);
        $response   = $this->post($this->config->apiUrl, $fullParams, false);
        $decode     = self::decrypt($response, $this->config->secret);
        $result     = json_decode($decode);

        // 如果解不開，就直接把錯誤丟回去
        if ($decode === false || $result === null) {
            throw new JSONException(get_class($this), 'error on JSON decode !', $result);
        }

        return $result;
    }

    private function setParams($params, $method)
    {
        $params = [
            'Request' => self::encrypt($params, $this->config->secret),
            'Method'  => $method,
            'AGID'    => $this->config->agid,
        ];

        if ($this->version !== null) {
            $params['Version'] = $this->version;
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
