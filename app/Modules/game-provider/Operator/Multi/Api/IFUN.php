<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Member;
use App\Models\Report;
use Carbon\Carbon;
use DB;
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
use GameProvider\Operator\Multi\Config\IFUNConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
// use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;

class IFUN extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $errorMessage = [
        0  => 'Success',
        1  => 'Invalid vendor key or IP not whitelisted',
        2  => 'User session not found',
        3  => 'User session expired',
        4  => 'Player account not found',
        5  => 'Invalid amount',
        6  => 'Invalid currency',
        7  => 'Insufficient balance',
        8  => 'Duplicated transaction ID',
        9  => 'Invalid parameter',
        10 => 'Invalid format',
        11 => 'Invalid date range',
        12 => 'Failed. See responsed error message for details',
        13 => 'Detail losed',
        14 => 'Game not found',
        15 => 'Game under maintenance',
        16 => 'Create game session failed',
    ];

    public function __construct(array $config)
    {
        $this->config             = new IFUNConfigConstract();
        $this->config->apiUrl     = $config['apiUrl'];
        $this->config->secret     = $config['secret'];
        $this->config->partner_id = $config['partner_id'];
        $this->config->currency   = $config['currency'];
        $this->config->lang       = $config['lang'];

        $this->doStuck = false;
    }

    public function getGameList()
    {
        $url = '/game/list';

        $params = [
            'partner_id' => $this->config->partner_id,
        ];
        $result = $this->doSendProcess($params, $url);
        return $result->list;
    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {

        $url = '/game/user';

        $params = [
            'partner_id' => $this->config->partner_id,
            'username'   => $member->playerId,
            'password'   => $member->password,
            'currency'   => $this->config->currency,
        ];

        $result         = $this->doSendProcess($params, $url);
        $memberFeedback = new MemberFeedback();

        if ($result->error === 0) {
            $memberFeedback->extendParam = $result->username;
            return $memberFeedback;
        }

        throw new CreateMemberException(get_class($this), 'create member error! error code : ' . $result->error, $this->errorMessage[$result->error]);
    }

    /**
     * 存款
     *
     * @param TransferParameter $transfer
     * @return MemberFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $url = '/game/deposit';

        $params = [
            'partner_id' => $this->config->partner_id,
            'username'   => $transfer->member->playerId,
            'amount'     => $transfer->amount,
            'ref_id'     => $this->GUID(),
            'currency'   => $this->config->currency,
        ];

        $transferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess($params, $url);

        if ($result->error === 0) {
            $transferFeedback->balance      = $result->balance;
            $transferFeedback->remote_payno = $result->trans_id;

            return $transferFeedback;
        }

        // 發生錯誤
        throw new TransferException(
            get_class($this),
            'deposit error! error code : ' . $result->error,
            $this->errorMessage[$result->error],
            $this->doStuck
        );
    }

    /**
     * 提款
     *
     * @param TransferParameter $transfer
     * @return MemberFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $url = '/game/withdrawal';

        $params = [
            'partner_id' => $this->config->partner_id,
            'username'   => $transfer->member->playerId,
            'amount'     => $transfer->amount,
            'ref_id'     => $this->GUID(),
            'currency'   => $this->config->currency,
        ];

        $transferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess($params, $url);

        if ($result->error === 0) {
            $transferFeedback->balance      = $result->balance;
            $transferFeedback->remote_payno = $result->trans_id;
            return $transferFeedback;
        }

        throw new TransferException(
            get_class($this),
            'withdraw error! error code : ' . $result->error,
            $this->errorMessage[$result->error],
            $this->doStuck
        );
    }

    /**
     * 會員登入（取得遊戲路徑）.
     *
     * @param \MultiWallet\Params\LaunchGameParameter $launchGameParams
     * @return \MultiWallet\Feedback\LaunchGameFeedback
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $url = '/game/open';

        $params = [
            'partner_id' => $this->config->partner_id,
            'game_code'  => $launchGameParams->gameId,
            'username'   => $launchGameParams->member->playerId,
            'ip'         => request()->ip(),
            'lang'       => $this->config->lang,
        ];

        $launchGameFeedback = new LaunchGameFeedback();

        $result = $this->doSendProcess($params, $url);

        if ($result->error === 0) {
            $launchGameFeedback->gameUrl       = $result->url;
            $launchGameFeedback->mobileGameUrl = $result->url;

            return $launchGameFeedback;
        }

        // 發生錯誤
        throw new LaunchGameException(get_class($this), 'launch game error! error code : ' . $result->error, $this->errorMessage[$result->error]);
    }

    /**
     * 取得會員餘額.
     *
     * @param \MultiWallet\Params\MemberParameter $member
     * @return \MultiWallet\Feedback\BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        $url = '/game/balance';

        $params = [
            'partner_id' => $this->config->partner_id,
            'username'   => $member->playerId,
        ];

        $feedback = new BalanceFeedback();
        $result   = $this->doSendProcess($params, $url);

        if ($result->error === 0) {
            $feedback->balance = $result->balance;
            return $feedback;
        }

        throw new BalanceException(get_class($this), 'get balance error! error code : ' . $result->error, $this->errorMessage[$result->error]);
    }

    /**
     * 同步注單
     *
     * @param \MultiWallet\Params\SyncReportParameters $srp
     * @param callable $callback
     * @return \MultiWallet\Feedback\SyncCallBackFeedback
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $url    = '/game/bethistory';
        $start  = Carbon::parse($srp->startAt)->format('Y-m-d H:i:s');
        $end    = Carbon::parse($srp->endAt)->format('Y-m-d H:i:s');
        $params = [
            'partner_id' => $this->config->partner_id,
            'starttime'  => $start,
            'endtime'    => $end,
            'rows'       => 1000,
            'page'       => 1,
        ];

        return $callback($this->doSyncReport($params, $url));
    }

    /**
     * @param \MultiWallet\Feedback\BaseFeedback $feedback
     * @param array $params
     * @return mix
     */
    private function doSendProcess(array $params, $url)
    {
        $fullParams = json_encode($this->doParamsEncode($params));

        $response = $this->post($this->config->apiUrl . $url, $fullParams, false);
        $result   = json_decode($response);

        // 如果解不開，就直接把錯誤丟回去
        if ($result === null) {
            throw new JSONException(get_class($this), 'error on JSON decode !', $result);
        }

        return $result;
    }

    /**
     * 參數加密.
     *
     * @param array $params
     * @return array
     */
    private function doParamsEncode(array $params)
    {
        $params = collect($params);
        // 所有參數不包含 HASH，由A-Z soft排序
        $params = $params->sortKeys();

        $paramStr = '';
        foreach ($params->toArray() as $key => $val) {
            if ($paramStr !== '') {
                $paramStr .= '&';
            }

            $paramStr .= $key . '=' . $val;
        }

        // 將 secret 加至參數最後方
        // $hash = implode("&", $params->toArray());
        // $hash = md5($hash . $this->config->secret);
        // $params->put('hash', $hash);

        // return $params->all();

        // $param = implode("&", $params->toArray());
        $hash               = md5($paramStr . $this->config->secret);
        $fullParams         = $params->toArray();
        $fullParams['hash'] = $hash;

        return $fullParams;
    }

    /**
     * @return array
     */
    private function doSyncReport($params, $url)
    {
        // $feedback = new SyncCallBackFeedback();
        $result = $this->doSendProcess($params, $url);

        if ($result->error === 0) {
            $rows = $result->bets;

            $data = [];

            foreach ($rows as $row) {
                $data[] = $this->makeSyncCallBackParameter($row);
            }

            if ($result->total > $params['page']) {
                $params['page'] = $params['page'] + 1;
                $data           = array_merge($data, $this->doSyncReport($params, $url));
            }

            return $data;
        }

        throw new SyncException(get_class($this), 'sync error! error code : ' . $result->error, $this->errorMessage[$result->error]);
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid         = $row->bet_id;
        $callBackParam->username    = $row->username;
        $callBackParam->gameCode    = $row->game_code;
        $callBackParam->betAmount   = $row->bet;
        $callBackParam->validAmount = $row->bet;
        // 我們是看不同文件嗎? vgold是什麼?
        // $callBackParam->validAmount = $row->vgold;
        $callBackParam->winAmount   = $row->win + $row->bet;
        $callBackParam->gameAt      = $row->game_time;
        $callBackParam->betAt       = $row->game_time;
        $callBackParam->reportAt    = $row->game_time;

        $status = [
            0 => Report::STATUS_BETTING,
            1 => Report::STATUS_COMPLETED,
            2 => Report::STATUS_CANCEL,
        ];

        $callBackParam->status = $status[$row->status];

        return $callBackParam;
    }

    public function langTrans(MemberParameter $member)
    {
        $lang = [
            'en'      => 'en',
            'zh-Hans' => 'cn',
            'zh-Hant' => 'tw',
            'ja'      => 'jp',
        ];

        $curLang = $lang[$member->language];

        return $curLang;
    }
}
