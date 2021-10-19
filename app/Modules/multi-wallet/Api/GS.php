<?php

namespace MultiWallet\Api;

use MultiWallet\Api\Config\GSConfigConstract;

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

class GS extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $token = null;

    protected $errorMessage = [
        '0' => '成功',
        '1' => '找不到合作商',
        '2' => '未經授權的訪問',
        '3' => '會員已存在',
        '4' => '找不到該會員',
        '5' => '金額錯誤',
        '7' => '餘額不足',
        '9' => '參數錯誤',
        '10' => '格式錯誤',
        '11' => '日期區間錯誤',
        '12' => '未知錯誤',
        '13' => '會員已被停用,請聯絡窗口',
        '14' => '會員建立失敗,找不到該代理',
        '15' => '組織名稱重複',
        '16' => '上層組織未建立',
        '17' => '找不到代理',
    ];

    function __construct(array $config)
    {
        $this->config = new GSConfigConstract();
        $this->config->apiUrl   = $config['apiUrl'];
        $this->config->username = $config['username'];
        $this->config->password = $config['password'];
        $this->config->secret   = $config['secret'];
        $this->config->agid     = $config['agid'];
    }

    /**
     * 獲取遊戲列表
     *
     * @return void
     */
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
        $params = [
            "agent_name" => $this->config->username,
            "partner_id" =>  $this->config->agid,
            "username" => $member->username
        ];
        $url = '?c=301';
        $memberFeedback = new MemberFeedback();
        $result = $this->doSendProcess($memberFeedback, $params, $url);

        if ($result instanceof MemberFeedback) {
            return $result;
        }

        if ($result->error === '0') {
            $memberFeedback->extendParam = $result->username;

            return $memberFeedback;
        }

        $memberFeedback->error_code = $result->error;
        $memberFeedback->error_msg = $this->errorMessage[$result->error];

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
        $payno = md5($transfer->member->playerId . time());
        $params = [
            "partner_id" =>  $this->config->agid,
            "username" => $transfer->member->username,
            "amount" => $transfer->anount,  //金額
            "ref_id" =>  $payno,  //單據編號
        ];
        $url = '?c=322';
        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($transferFeedback, $params, $url);

        if ($result instanceof TransferFeedback) {
            return $result;
        }

        if ($result->error === '0') {
            $transferFeedback->balance = $result->balance;
            $transferFeedback->remote_payno = $payno;
            $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }

        // 發生錯誤
        $transferFeedback->error_code = $result->error;
        $transferFeedback->error_msg = $this->errorMessage[$result->error];

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
        $payno = md5($transfer->member->playerId . time());
        $params = [
            "partner_id" =>  $this->config->agid,
            "username" => $transfer->member->username,
            "amount" => $transfer->anount,  //金額
            "ref_id" =>  $payno,  //單據編號
        ];
        $url = '?c=324';
        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($transferFeedback, $params, $url);

        if ($result instanceof TransferFeedback) {
            return $result;
        }

        if ($result->error === '0') {
            $transferFeedback->balance = $result->balance;
            $transferFeedback->remote_payno = $payno;
            $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }

        // 發生錯誤
        $transferFeedback->error_code = $result->error;
        $transferFeedback->error_msg = $this->errorMessage[$result->error];

        return $transferFeedback;
    }

    /**
     * 會員登入（取得遊戲路徑）
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $params = [
            "partner_id" =>  $this->config->agid,
            "username" => $launchGameParams->member->username,
        ];

        $url = '?c=302';
        $launchGameFeedback = new LaunchGameFeedback();

        $result = $this->doSendProcess($launchGameFeedback, $params, $url);

        if($result instanceof LaunchGameFeedback)
        {
            return $result;
        }

        $key = $result->key;
        $launchgameurl = $this->config->apiUrl.'?key='.$key;
        $mobileGameUrl = $this->config->apiUrl.'?key='.$key.'&device=mobile';

        if ($result->error === '0') {
            $launchGameFeedback->gameUrl = $launchgameurl;
            $launchGameFeedback->mobileGameUrl = $mobileGameUrl;
            $launchGameFeedback->response_cod = $this->reponseCode;

            return $launchGameFeedback;
        }

        // 發生錯誤
        $launchGameFeedback->error_code = $result->error;
        $launchGameFeedback->error_msg = $this->errorMessage[$result->error];

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
        $params = [
            "partner_id" =>  $this->config->agid,
            "username" => $member->username
        ];
        $url = '?c=310';

        $balanceFeedback = new BalanceFeedback();

        $result = $this->doSendProcess($balanceFeedback, $params, $url);

        if ($result instanceof BalanceFeedback) {
            return $result;
        }

        if ($result->error === '0') {
            $balanceFeedback->response_code = $this->error;
            $balanceFeedback->balance = $result->balance;

            return $balanceFeedback;
        }

        // 發生錯誤
        $balanceFeedback->error_code = $result->error;
        $balanceFeedback->error_msg = $this->errorMessage[$result->error];

        return $balanceFeedback;
    }

    /**
     * 同步注單(取回時間區段的所有注單)
     *
     * @return void
     */
    public function syncReport(SyncReportParameters $srp, callable $callback)
    {
        $params = [
            "partner_id" =>  $this->config->agid,
            'starttime' => $srp->startAt,
            'endtime'   => $srp->endAt,
        ];

        return $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
        $callBackFeedback = new SyncCallBackFeedback();
        $url = '?c=330';

        $result = $this->doSendProcess($callBackFeedback, $params, $url);

        if($result instanceof SyncCallBackFeedback)
        {
            return $result;
        }

        if($result->error === '0')
        {
            $rows = $result->bets;

            $data = [];

            foreach($rows as $row)
            {
                $data[] = $this->makeSyncCallBackParameter($row);
            }

            return $data;
        }

        $callBackFeedback->error_code = $result->error;
        $callBackFeedback->error_msg = $this->errorMessage[$result->error];

        return $callBackFeedback;
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid = $row->bet_id;
        $callBackParam->username = $row->username;
        $callBackParam->betAmount = $row->bet;
        $callBackParam->winAmount = $row->win;
        $callBackParam->betAt = $row->time;

        $callBackParam->content = $row->detail;

        $status = [
            0 => Report::STATUS_BETTING,
            1 => Report::STATUS_COMPLETED,
            2 => Report::STATUS_CANCEL,
        ];

        $callBackParam->status = $status[$row->status];

        return $callBackParam;
    }

    private function doSendProcess($feedback, $params, $url)
    {
        $fullParams = $this->setParams($params);
        $apiUrl = $this->config->apiUrl . $url;
        $response = $this->post($apiUrl, $fullParams, false);

        $result = json_decode($response);
        // 肯定出問題了
        if ($this->reponseCode != 200) {
            $feedback->error_code = static::RESPONSE_ERROR;
            $feedback->response_code = $this->reponseCode;
            $feedback->error_msg = '對方似乎報錯:' . $this->reponseCode;

            return $feedback;
        }

        return $result;
    }

    private function setParams($params)
    {
        $paramsTrans = ksort($params);
        $paramsTrans = http_build_query($paramsTrans);
        $paramsTrans = $paramsTrans . $this->config->secret;
        $hash = md5($params);

        $params['hash'] = $hash;
        return $params;
    }
}
