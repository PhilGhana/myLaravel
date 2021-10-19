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
use GameProvider\Operator\Multi\Config\MGConfigConstruct;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
// use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;

class Sport3652 extends BaseApi implements BaseMultiWalletInterface
{
    const ERRORS = [
        100001 => '功能代碼錯誤',
        100002 => 'hash檢查錯誤',
        100003 => 'APIpartner錯誤',
        100004 => '傳輸參數錯誤',
        290001 => 'API參數username為空',
        290002 => '登入失敗',
        290005 => '進入遊戲大廳失敗',
        720001 => '無此管理帳號',
        720002 => '管理端URL參數錯誤',
        661001 => '管理帳號不存在',
        661002 => '管理帳號停用',
        661003 => '會員帳號已存在',
        661004 => '新增失敗',
        660001 => '管理帳號已存在',
        660002 => '沒有這個上層帳號',
        660003 => '取上層資料錯誤',
        660004 => '新增管理帳號出錯',
        660005 => '階層錯誤',
        606001 => 'sdate or edate 沒有資料',
        606002 => '時間範圍錯誤',
        606003 => '錯誤的APIpartner值',
        606004 => '無帳務資料',
        606005 => '超出查詢時間範圍',
        271001 => '取剩餘額度失敗',
        272001 => '查詢會員存/提值LOG失敗-transacid為空',
        272002 => '查詢會員存/提值LOG失敗-transacid不存在',
        270001 => '存/提值額度格式非整數',
        270002 => '會員不存在',
        270003 => '存提時間間隔太短請稍後再執行',
        270004 => 'transacid為空',
        270005 => 'transacid已存在',
        270006 => '額度不足',
    ];

    protected $config;

    public function __construct(array $config)
    {
        $this->config             = new MGConfigConstruct();
        $this->config->apiUrl     = $config['apiUrl'];
        $this->config->APIpartner = $config['APIpartner'];
        $this->config->secret     = $config['secret'];
    }

    public function getGameList()
    {
    }

    public function getErrMsg($code)
    {
        return self::ERRORS[$code] ?? 'Unknow Error';
    }

    public function getManageWindow($account)
    {
        $params = [
            'APIpartner'   => $this->config->APIpartner,
            'function'     => 'getManageWindow',
            'username'     => $account,
        ];

        $result = $this->doSendProcess($params);

        if ($result->err === false) {
            throw new \Exception('getManageWindow error: '.$account.' : '.$result->err_msg);
        }

        return $result->ret;
    }

    // public function addManageAccount($account)
    // {
    //     // $params = [
    //     //     'mem'      => 'lv1',
    //     //     'lvl'      => 6,
    //     //     'age'      => 'MG_manage',
    //     //     'function' => 'addManageAccount',
    //     // ];

    //     // $result = $this->doSendProcess($params);

    //     // $params = [
    //     //     'mem'      => 'lv2',
    //     //     'lvl'      => 5,
    //     //     'age'      => 'lv1',
    //     //     'function' => 'addManageAccount',
    //     // ];

    //     // $result = $this->doSendProcess($params);

    //     // $params = [
    //     //     'mem'      => 'lv3',
    //     //     'lvl'      => 4,
    //     //     'age'      => 'lv2',
    //     //     'function' => 'addManageAccount',
    //     // ];

    //     // $result = $this->doSendProcess($params);

    //     // $params = [
    //     //     'mem'      => 'lv4',
    //     //     'lvl'      => 3,
    //     //     'age'      => 'lv3',
    //     //     'function' => 'addManageAccount',
    //     // ];

    //     // $result = $this->doSendProcess($params);

    //     $params = [
    //         'mem'      => $account,
    //         'lvl'      => 2,
    //         'age'      => 'lv4',
    //         'function' => 'addManageAccount',
    //     ];

    //     $result = $this->doSendProcess($params);

    //     // if ($result->err === false) {
    //     //     throw new CreateMemberException(get_class($this), 'create agent error! error code : ' . $result->err_msg . ' ' . $this->errorMessage[$result->err_msg], $this->errorMessage[$result->err_msg]);
    //     // }

    // }

    public function addManageAccount($age, $lv, $account, $name)
    {
        $parent_acc = $age;
        if ($lv == 6) {
            $parent_acc = '';
        }

        $params = [
            'APIpartner'   => $this->config->APIpartner,
            'function'     => 'addManageAccount',
            'lvl'          => $lv,
            'username'     => $account,
            'alias'        => urlencode($name),
            'upusername'   => $parent_acc,
        ];

        $result = $this->doSendProcess($params);

        if ($result->err === false) {
            throw new \Exception('addManageAccount error : '.$result->err_msg.' age:'.$parent_acc.' lv:'.$lv.' account:'.$account.' name:'.$name);
        }
    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        // 獲取上層帳號
        // $agent = Member::where('member.id', $member->member_id)
        //     ->join('agent', 'agent.id', '=', 'alv5')
        //     ->select(DB::RAW("agent.account as account"))
        //     ->first();

        // // $this->addManageAccount($agent->account);

        // $params = [
        //     'mem'      => $member->playerId,
        //     'age'      => $agent->account,
        //     'function' => 'addMemberAccount',
        // ];

        // $result = $this->doSendProcess($params);

        $agent = Member::where('member.id', $member->member_id)
            ->join('agent', 'agent.id', '=', 'alv5')
            ->select(DB::RAW('agent.account as account, agent.name as name'))
            ->first();

        $params = [
            'APIpartner'  => $this->config->APIpartner,
            'function'    => 'addMemberAccount',
            'username'    => $member->playerId,
            'age'         => $agent->account,
        ];

        $result = $this->doSendProcess($params);

        if ($result->err === false) {
            $msg = $this->getErrMsg($result->err_msg);
            throw new CreateMemberException(get_class($this), 'create member error! error code : '.$result->err_msg.' '.$msg, $msg);
        }

        $memberFeedback              = new MemberFeedback();
        $memberFeedback->extendParam = $member->username;

        return $memberFeedback;
    }

    /**
     * 存款.
     *
     * @param TransferParameter $transfer
     * @return MemberFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $payno = $this->GUID();

        $params = [
            'APIpartner'  => $this->config->APIpartner,
            'Credits'     => $transfer->amount,
            'transacid'   => $payno,
            'username'    => $transfer->member->playerId,
            'function'    => 'doDeposit',
        ];

        $transferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess($params);

        if ($result->err === false) {
            // $msg = $this->getErrMsg($result->err_msg);
            throw new TransferException(
                get_class($this),
                $result->err_msg.' '.self::ERRORS[$result->err_msg],
                $result->err_msg.' '.self::ERRORS[$result->err_msg]
            );
        }

        $transferFeedback->balance      = $result->ret;
        $transferFeedback->remote_payno = $payno;

        return $transferFeedback;
    }

    /**
     * 提款.
     *
     * @param TransferParameter $transfer
     * @return MemberFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $payno = $this->GUID();

        $params = [
            'APIpartner'  => $this->config->APIpartner,
            'Credits'     => $transfer->amount,
            'transacid'   => $payno,
            'username'    => $transfer->member->playerId,
            'function'    => 'doWithdrawal',
        ];

        $transferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess($params);

        if ($result->err === false) {
            // $msg = $this->getErrMsg($result->err_msg);
            throw new TransferException(
                get_class($this),
                $result->err_msg.' '.self::ERRORS[$result->err_msg] ?? '',
                $result->err_msg.' '.self::ERRORS[$result->err_msg] ?? ''
            );
        }

        $transferFeedback->balance      = $result->ret;
        $transferFeedback->remote_payno = $payno;

        return $transferFeedback;
    }

    /**
     * 會員登入（取得遊戲路徑）.
     *
     * @param \MultiWallet\Params\LaunchGameParameter $launchGameParams
     * @return \MultiWallet\Feedback\LaunchGameFeedback
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $params = [
            'function' => 'domemlogin',
            'username' => $launchGameParams->member->playerId, // 會員帳號
        ];

        $launchGameFeedback = new LaunchGameFeedback();
        $result             = $this->doSendProcess($params);

        if ($result->err === false) {
            $msg = $this->getErrMsg($result->err_msg);
            throw new LaunchGameException(get_class($this), 'launch game error! error code : '.$result->err_msg.' '.$msg, $msg);
        }

        $launchGameFeedback->gameUrl       = $result->ret;
        $launchGameFeedback->mobileGameUrl = $result->ret; // 不確定是否要手機連結

        return $launchGameFeedback;
    }

    /**
     * 取得會員餘額.
     *
     * @param \MultiWallet\Params\MemberParameter $member
     * @return \MultiWallet\Feedback\BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        $params = [
            'APIpartner'  => $this->config->APIpartner,
            'function'    => 'getMemberPoint',
            'username'    => $member->playerId,
        ];

        $feedback = new BalanceFeedback();
        $result   = $this->doSendProcess($params);

        if ($result->err === false) {
            $msg = $this->getErrMsg($result->err_msg);
            throw new BalanceException(get_class($this), 'get balance error! error code : '.$result->err_msg.' '.$msg, $msg);
        }

        $feedback->balance = $result->ret;

        return $feedback;
    }

    /**
     * 同步注單.
     *
     * @param \MultiWallet\Params\SyncReportParameters $srp
     * @param callable $callback
     * @return \MultiWallet\Feedback\SyncCallBackFeedback
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $start  = Carbon::parse($srp->startAt)->format('Y-m-d H:i:s');
        $end    = Carbon::parse($srp->endAt)->format('Y-m-d H:i:s');
        // 時間相同時, 要加一小時
        if ($start == $end) {
            $end    = Carbon::parse($srp->endAt)->addHour()->format('Y-m-d G:00:00');
        }
        // $start  = $srp->startAt;
        // $end    = $srp->endAt;
        $params = [
            'APIpartner'  => $this->config->APIpartner,
            'function'    => 'getDetailReport',
            'sdate'       => $start,
            'edate'       => $end,
            'settlement'  => 'N',
        ];

        $callback($this->doSyncReport($params));

        $params['settlement'] = 'Y';

        return $callback($this->doSyncReport($params));
    }

    /**
     * @param \MultiWallet\Feedback\BaseFeedback $feedback
     * @param array $params
     * @return mix
     */
    private function doSendProcess(array $params)
    {
        $fullParams = $this->doParamsEncode($params);
        $response   = $this->get($this->config->apiUrl.'?'.$fullParams, $fullParams, true);

        return $response;
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
        $params->put('APIpartner', $this->config->APIpartner);

        // 所有參數不包含 HASH，由A-Z soft排序
        $params = $params->sortKeys();

        $paramStr = '';
        foreach ($params->toArray() as $key => $val) {
            if ($paramStr !== '') {
                $paramStr .= '&';
            }

            $paramStr .= $key.'='.urlencode($val);
        }

        // 將 secret 加至參數最後方
        // $hash = implode("&", $params->toArray());
        // $hash = md5($hash . $this->config->secret);
        // $params->put('hash', $hash);

        // return $params->all();

        // $param = implode("&", $params->toArray());
        $hash = md5($paramStr.$this->config->secret);

        return $paramStr.'&hash='.$hash;
    }

    /**
     * @return array
     */
    private function doSyncReport($params)
    {
        // $feedback = new SyncCallBackFeedback();
        $result   = $this->doSendProcess($params);

        if ($result->err === 'false') {
            $msg = $this->getErrMsg($result->err_msg);
            throw new SyncException(get_class($this), 'sync error! error code : '.$result->err_msg.' '.$msg, $msg);
        }

        // 注意: 沒單的時候他會沒給值
        $result = $result->ret ?? null;
        if (! $result) {
            return [];
        }
        $rows   = $result->report ?? [];
        $data   = [];

        foreach ($rows as $row_account) {
            foreach ($row_account as $row) {
                $data[] = $this->makeSyncCallBackParameter($row);
            }
        }

        // if ($result->allpage > $result->thispage) {
        //     $params['page'] = $params['page'] + 1;
        //     $data           = array_merge($data, $this->doSyncReport($params));
        // }
        return $data;
    }

    private function makeSyncCallBackParameter($row)
    {
        $prefix                  = $this->config->APIpartner ? ($this->config->APIpartner.'_') : '';
        $callBackParam           = new SyncCallBackParameter();
        $callBackParam->mid      = $row->id; // 注單ID
        $callBackParam->gameCode = '3652';
        $callBackParam->username = strtolower(str_replace($prefix, '', $row->mem_alias)); // 下注會員帳號，20210530暫時用替代字串方式處理，後續調整為較穩的方式
        $callBackParam->betAt    = $row->adddate; // 下注時間(GMT+8)
        $callBackParam->reportAt = $row->adddate; // 結算時間 改成投注時間，不然會查不到
        // $callBackParam->settleAt = $row->settlementTime;
        $callBackParam->table   = $row->gtype ?? 'UNKNOW';
        $callBackParam->round   = $row->gid ?? (strval($row->star ?? '0').'X1');
        $callBackParam->content = $row->content;
        // $callBackParam->waterAmount = ;
        $callBackParam->betAmount   = $row->gold; // 下注時間金額
        $callBackParam->validAmount = $row->effective_gold; // 有效下注
        $callBackParam->winAmount   = ($row->wingold == '0.00') ? $row->gold : ($row->wingold + $row->gold); // 輸贏金額
        // $callBackParam->prize = ;
        // $callBackParam->tip = ;
        $callBackParam->ip     = $row->orderIP; //下注IP

        $status = [
            '0'  => Report::STATUS_BETTING,
            'N'  => Report::STATUS_CANCEL,
            'NC' => Report::STATUS_CANCEL,
            'W'  => Report::STATUS_COMPLETED,
            'L'  => Report::STATUS_COMPLETED,
            'LL' => Report::STATUS_COMPLETED,
            'LW' => Report::STATUS_COMPLETED,
        ];

        $callBackParam->status = $status[$row->result]; // 結果「W(贏)、L(輸)、LL(中分洞輸)、LW(中分洞贏)、N(取消比賽)、NC(取消注單)、0(尚無結果)」

        return $callBackParam;
    }
}
