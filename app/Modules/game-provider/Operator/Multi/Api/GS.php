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
use GameProvider\Operator\Multi\Config\GSConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
// use MultiWallet\Feedback\SyncCallBackFeedback;
use GameProvider\Operator\Params\TransferParameter;
use Illuminate\Support\Str;

class GS extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $token = null;

    protected $errorMessage = [
        '0'  => '成功',
        '1'  => '找不到合作商',
        '2'  => '未經授權的訪問',
        '3'  => '會員已存在',
        '4'  => '找不到該會員',
        '5'  => '金額錯誤',
        '7'  => '餘額不足',
        '9'  => '參數錯誤',
        '10' => '格式錯誤',
        '11' => '日期區間錯誤',
        '12' => '未知錯誤',
        '13' => '會員已被停用,請聯絡窗口',
        '14' => '會員建立失敗,找不到該代理',
        '15' => '組織名稱重複',
        '16' => '上層組織未建立',
        '17' => '找不到代理',
    ];

    public function __construct(array $config)
    {
        $this->config           = new GSConfigConstract();
        $this->config->apiUrl   = Str::finish($config['apiUrl'], '/');
        $this->config->username = $config['username'];
        // $this->config->password = $config['password'];
        $this->config->secret   = $config['secret'];
        $this->config->agid     = $config['agid'];
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
        $params = [
            'agent_name' => $this->config->username,
            'partner_id' => $this->config->agid,
            'username'   => $member->playerId,
        ];
        $url            = '?c=301';
        $memberFeedback = new MemberFeedback();
        $result         = $this->doSendProcess($params, $url);

        if ($result instanceof MemberFeedback) {
            return $result;
        }

        if ($result->error === 0) {
            $memberFeedback->extendParam = $result->username;

            return $memberFeedback;
        }

        throw new CreateMemberException(get_class($this), 'create member error! error code : '.$result->error, $this->errorMessage[$result->error]);
        // $memberFeedback->error_code = $result->error;
        // $memberFeedback->error_msg = $this->errorMessage[$result->error];

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
        $payno  = md5($transfer->member->playerId.time());
        $params = [
            'partner_id' => $this->config->agid,
            'username'   => $transfer->member->playerId,
            'amount'     => $transfer->amount,  //金額
            'ref_id'     => $payno,  //單據編號
        ];

        $url              = '?c=322';
        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($params, $url);

        if ($result->error === 0) {
            $transferFeedback->balance       = $result->balance;
            $transferFeedback->remote_payno  = $payno;
            // $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }

        // 發生錯誤
        throw new TransferException(
            get_class($this),
            $this->errorMessage[$result->error] ?? ('Unknow:'.$result->error),
            $this->errorMessage[$result->error] ?? ('Unknow:'.$result->error)
        );
        // $transferFeedback->error_code = $result->error;
        // $transferFeedback->error_msg = $this->errorMessage[$result->error];

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
        $payno  = md5($transfer->member->playerId.time());
        $params = [
            'partner_id' => $this->config->agid,
            'username'   => $transfer->member->playerId,
            'amount'     => $transfer->amount,  //金額
            'ref_id'     => $payno,  //單據編號
        ];
        $url              = '?c=324';
        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($params, $url);

        if ($result->error === 0) {
            $transferFeedback->balance       = $result->balance;
            $transferFeedback->remote_payno  = $payno;
            // $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }

        // 發生錯誤
        throw new TransferException(
            get_class($this),
            $this->errorMessage[$result->error] ?? ('Unknow:'.$result->error),
            $this->errorMessage[$result->error] ?? ('Unknow:'.$result->error)
        );
        // $transferFeedback->error_code = $result->error;
        // $transferFeedback->error_msg = $this->errorMessage[$result->error];

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
        $params = [
            'partner_id' => $this->config->agid,
            'username'   => $launchGameParams->member->playerId,
        ];

        $url                = '?c=302';
        $launchGameFeedback = new LaunchGameFeedback();

        $result = $this->doSendProcess($params, $url);

        $key           = $result->key;
        $launchgameurl = $this->config->apiUrl.'?key='.$key;
        $mobileGameUrl = $this->config->apiUrl.'?key='.$key.'&device=mobile';

        if ($result->error === 0) {
            $launchGameFeedback->gameUrl       = $launchgameurl;
            $launchGameFeedback->mobileGameUrl = $mobileGameUrl;
            // $launchGameFeedback->response_cod  = $this->reponseCode;

            return $launchGameFeedback;
        }

        // 發生錯誤
        throw new LaunchGameException(get_class($this), 'launch game error! error code : '.$result->error, $this->errorMessage[$result->error]);
        // $launchGameFeedback->error_code = $result->error;
        // $launchGameFeedback->error_msg = $this->errorMessage[$result->error];

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
        $params = [
            'partner_id' => $this->config->agid,
            'username'   => $member->playerId,
        ];
        $url = '?c=310';

        $balanceFeedback = new BalanceFeedback();

        $result = $this->doSendProcess($params, $url);

        if ($result->error === 0) {
            $balanceFeedback->response_code = $result->error;
            $balanceFeedback->balance       = $result->balance;

            return $balanceFeedback;
        }

        // 發生錯誤
        throw new BalanceException(get_class($this), 'get balance error! error code : '.$result->error, $this->errorMessage[$result->error]);
        // $balanceFeedback->error_code = $result->error;
        // $balanceFeedback->error_msg = $this->errorMessage[$result->error];

        // return $balanceFeedback;
    }

    /**
     * 同步注單(取回時間區段的所有注單).
     *
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $params = [
            'partner_id' => $this->config->agid,
            'starttime'  => $srp->startAt,
            'endtime'    => $srp->endAt,
        ];

        return $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
        $url = '?c=330';

        $result = $this->doSendProcess($params, $url);

        if ($result->error == '0') {
            $rows = $result->bets;

            $data = [];

            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $data[] = $this->makeSyncCallBackParameter($row);
                }
            }

            return $data;
        }

        throw new SyncException(get_class($this), 'sync error! error code : '.$result->error, $this->errorMessage[$result->error]);
        // $callBackFeedback->error_code = $result->error;
        // $callBackFeedback->error_msg = $this->errorMessage[$result->error];

        // return $callBackFeedback;
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid         = $row->bet_id;
        $callBackParam->gameCode    = 'GS';
        $callBackParam->username    = $row->username;
        $callBackParam->reportAt    = $row->time;
        $callBackParam->betAmount   = $row->bet;
        $callBackParam->validAmount = $row->bet;
        $callBackParam->winAmount   = $row->win + $row->bet;
        $callBackParam->betAt       = $row->time;
        $callBackParam->table       = ($row->detail->type_id ?? '').'_'.($row->detail->play_id ?? '');
        $callBackParam->round       = $row->issue ?? 0;
        $callBackParam->content     = $row->detail;

        $status = [
            0 => Report::STATUS_BETTING,
            1 => Report::STATUS_COMPLETED,
            2 => Report::STATUS_CANCEL,
        ];

        $callBackParam->status = $status[$row->status];

        if ($callBackParam->status == Report::STATUS_CANCEL) {
            $callBackParam->validAmount = 0;
        }

        return $callBackParam;
    }

    private function doSendProcess($params, $url)
    {
        $fullParams = $this->setParams($params);
        $apiUrl     = $this->config->apiUrl.$url;
        $response   = $this->post($apiUrl, $fullParams, false);
        $result     = json_decode($this->removeBOM($response));
        // 肯定出問題了
        // if ($this->reponseCode != 200) {

        //     throw new JSONException(get_class($this), 'error on JSON decode !', $result);
        // }

        return $result;
    }

    private function setParams($params)
    {
        ksort($params);
        $paramsTrans = http_build_query($params);
        $paramsTrans = $paramsTrans.$this->config->secret;
        $hash        = md5($paramsTrans);

        $params['hash'] = $hash;

        return json_encode($params);
    }

    private function removeBOM($str = '')
    {
        if (substr($str, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
            $str = substr($str, 3);
        }

        return $str;
    }
}
