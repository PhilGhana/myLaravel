<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Member;
use Carbon\Carbon;

use DB;

use GameProvider\Operator\BaseApi;

use GameProvider\Operator\Multi\Config\MGConfigConstruct;
use GameProvider\Operator\Multi\BaseMultiWalletInterface;

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

use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\TransferParameter;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
// use GameProvider\Operator\Params\SyncReportParameter;
use App\Models\Report;

class MG extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $errorMessage = [
        '1'                        => '功能代碼錯誤',
        '2'                        => 'hash檢查錯誤',
        '3'                        => 'APIpartner錯誤',
        '4'                        => '傳輸參數錯誤',
        '5'                        => '進入遊戲大廳錯誤',
        'UID NULL'                 => '驗證碼沒有資料',
        'LVL NULL'                 => 'lvl 沒有資料',
        'MEM NULL'                 => 'mem沒有資料',
        'UID ERROR'                => '驗證碼錯誤',
        'LVL ERROR'                => 'lvl 錯誤',
        'agent not exist'          => '管理帳號不存在',
        'agent stop'               => '管理帳號停用',
        'mem exist'                => '會員帳號已存在',
        'mem add fail'             => '新增失敗',
        'upuser not exist'         => '沒有這個上層',
        'user exist'               => '帳號已存在',
        'agent add fail'           => '帳號新增失敗',
        'upuser data error'        => '取上層資料錯誤',
        'lvl Error'                => '階層錯誤',
        'add ag Error'             => '新增管理帳號出錯',
        'login error'              => '進入遊戲大廳失敗',
        'SDATE IS NUL'             => '起始時間沒資料',
        'EDATE IS NULL'            => '結束時間沒資料',
        'DATE RANGE IS ERROR'      => '時間範圍錯誤',
        'NO DATA'                  => '無帳務資料',
        'Enter the correct amount' => '輸入正確的額度',
        'Member not exist'         => '會員不存在',
        'Lack of credit'           => '額度不足',
        'logId is null'            => 'logId為空',
        'logId exist'              => 'logId已存在',
    ];

    public function __construct(array $config)
    {
        $this->config             = new MGConfigConstruct();
        $this->config->apiUrl     = $config['apiUrl'];
        $this->config->APIpartner = $config['APIpartner'];
        $this->config->secret     = $config['secret'];
        $this->config->age        = $config['age'];
    }

    public function getGameList()
    {

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
        if($lv == 6)
        {
            $parent_acc = $this->config->age;
        }

        $params = [
            'APIpartner'  => $this->config->APIpartner,
            'function'    => 'addManageAccount',
            'lvl'         => $lv,
            'mem'         => $account,
            'alias'       => urlencode($name),
            'age'         => $parent_acc,
        ];

        $result = $this->doSendProcess($params);

        if($result->err === false)
        {
            throw new Exception('addManageAccount error : ' . $result->err_msg . ' age:' . $parent_acc . ' lv:' . $lv . ' account:' . $account . ' name:' . $name );
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
            ->select(DB::RAW("agent.account as account, agent.name as name"))
            ->first();

        $params = [
            'APIpartner'  => $this->config->APIpartner,
            'function'    => 'addMemberAccount',
            'mem'         => $member->username,
            'pwd'         => $member->password,
            'age'         => $agent->account,
        ];

        $result = $this->doSendProcess($params);

        if ($result->err === false) {
            throw new CreateMemberException(get_class($this), 'create member error! error code : ' . $result->err_msg . ' ' . $this->errorMessage[$result->err_msg], $this->errorMessage[$result->err_msg]);
        }

        $memberFeedback              = new MemberFeedback();
        $memberFeedback->extendParam = $member->username;

        return $memberFeedback;
    }

    /**
     * 存款
     *
     * @param TransferParameter $transfer
     * @return MemberFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $payno = $this->GUID();

        $params = [
            'APIpartner'  => $this->config->APIpartner,
            'Credits'  => $transfer->amount,
            'logid'    => $payno,
            'mem'      => $transfer->member->playerId,
            'function' => 'doDeposit',
        ];

        $transferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess($params);

        if ($result->err === false) {
            throw new TransferException(get_class($this), 'deposit error! error code : ' . $result->err_msg . ' ' . $this->errorMessage[$result->err_msg], $this->errorMessage[$result->err_msg]);
        }

        $transferFeedback->balance      = $result->ret;
        $transferFeedback->remote_payno = $payno;

        return $transferFeedback;
    }

    /**
     * 提款
     *
     * @param TransferParameter $transfer
     * @return MemberFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $payno = $this->GUID();

        $params = [
            'APIpartner'  => $this->config->APIpartner,
            'Credits'  => $transfer->amount,
            'logid'    => $payno,
            'mem'      => $transfer->member->playerId,
            'function' => 'doWithdrawal',
        ];

        $transferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess($params);

        if ($result->err === false) {
            throw new TransferException(get_class($this), 'withdraw error! error code : ' . $result->err_msg . ' ' . $this->errorMessage[$result->err_msg], $this->errorMessage[$result->err_msg]);
        }

        $transferFeedback->balance      = $result->ret;
        $transferFeedback->remote_payno = $payno;

        return $transferFeedback;

    }

    /**
     * 會員登入（取得遊戲路徑）
     *
     * @param \MultiWallet\Params\LaunchGameParameter $launchGameParams
     * @return \MultiWallet\Feedback\LaunchGameFeedback
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $params = [
            'function' => 'domemlogin',
            'mem'      => $launchGameParams->member->playerId, // 會員帳號
            'uip'      => request()->ip(), // 會員IP
        ];

        $launchGameFeedback = new LaunchGameFeedback();
        $result             = $this->doSendProcess($params);

        if ($result->err === false){
            throw new LaunchGameException(get_class($this), 'launch game error! error code : ' . $result->err_msg . ' ' . $this->errorMessage[$result->err_msg], $this->errorMessage[$result->err_msg]);
        }
        
        $launchGameFeedback->gameUrl       = $result->ret;
        $launchGameFeedback->mobileGameUrl = $result->ret; // 不確定是否要手機連結

        return $launchGameFeedback;
    }

    /**
     * 取得會員餘額
     *
     * @param \MultiWallet\Params\MemberParameter $member
     * @return \MultiWallet\Feedback\BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        $params = [
            'APIpartner'  => $this->config->APIpartner,
            'function' => 'getMemberPoint',
            'mem'      => $member->playerId,
        ];

        $feedback = new BalanceFeedback();
        $result   = $this->doSendProcess($params);

        if ($result->err === false){
            throw new BalanceException(get_class($this), 'get balance error! error code : ' . $result->err_msg . ' ' . $this->errorMessage[$result->err_msg], $this->errorMessage[$result->err_msg]);
        }

        $feedback->balance = $result->ret;

        return $feedback;
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
        $start  = Carbon::parse($srp->startAt)->format('Y-m-d');
        $end    = Carbon::parse($srp->endAt)->format('Y-m-d');
        // $start  = $srp->startAt;
        // $end    = $srp->endAt;
        $params = [
            'APIpartner'  => $this->config->APIpartner,
            'function' => 'getDetailReport',
            'sdate'    => $start,
            'edate'    => $end,
            'page'     => 1,
        ];

        return $callback($this->doSyncReport($params));
    }

    /**
     *
     *
     * @param \MultiWallet\Feedback\BaseFeedback $feedback
     * @param array $params
     * @return mix
     */
    private function doSendProcess(array $params)
    {
        $fullParams = $this->doParamsEncode($params);
        $response   = $this->get($this->config->apiUrl . '?' . $fullParams, $fullParams, true);

        return $response;
    }

    /**
     * 參數加密
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
        foreach($params->toArray() as $key => $val) {

            if($paramStr !== '') {
                $paramStr .= '&';
            }

            $paramStr .= $key . '=' . urlencode($val);
        }

        // 將 secret 加至參數最後方
        // $hash = implode("&", $params->toArray());
        // $hash = md5($hash . $this->config->secret);
        // $params->put('hash', $hash);

        // return $params->all();

        // $param = implode("&", $params->toArray());
        $hash = md5($paramStr . $this->config->secret);

        return $paramStr . '&hash=' . $hash;
    }

    /**
     *
     *
     * @return array
     */
    private function doSyncReport($params)
    {
        // $feedback = new SyncCallBackFeedback();
        $result   = $this->doSendProcess($params);

        if($result->err === 'false'){
            throw new SyncException(get_class($this), 'sync error! error code : ' . $result->err_msg . ' ' . $this->errorMessage[$result->err_msg], $this->errorMessage[$result->err_msg]);
        }

        $result = $result->ret;
        $rows   = $result->report;
        $data   = [];

        foreach ($rows as $row_account) {
            
            foreach($row_account as $row) {
                $data[] = $this->makeSyncCallBackParameter($row);
            }
        }

        if ($result->allpage > $result->thispage) {
            $params['page'] = $params['page'] + 1;
            $data           = array_merge($data, $this->doSyncReport($params));
        }

        return $data;
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam           = new SyncCallBackParameter();
        $callBackParam->mid      = $row->id; // 注單ID
        $callBackParam->gameCode = 'MGMG';
        $callBackParam->username = str_replace('MGT_', '', $row->mem_alias); // "下注會員帳號
        $callBackParam->betAt    = $row->adddate; // 下注時間(GMT+8)
        $callBackParam->reportAt = $row->adddate; // 結算時間 改成投注時間，不然會查不到
        // $callBackParam->settleAt = $row->settlementTime;
        $callBackParam->table = $row->gid;
        $callBackParam->round = $row->gid;
        $callBackParam->content = $row->content;
        // $callBackParam->waterAmount = ;
        $callBackParam->betAmount   = $row->gold; // 下注時間金額
        $callBackParam->validAmount = $row->effective_gold; // 有效下注
        $callBackParam->winAmount   = ($row->wingold == '0.00')? 0:($row->wingold + $row->gold); // 輸贏金額
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
