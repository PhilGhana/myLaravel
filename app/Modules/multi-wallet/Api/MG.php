<?php

namespace MultiWallet\Api;

use App\Models\Member;
use Carbon\Carbon;
use MultiWallet\Api\Config\MGConfigConstruct;
use MultiWallet\Base\BaseMultiWalletInterface;
use MultiWallet\Exceptions\GameListException;
use MultiWallet\Exceptions\LoginException;
use MultiWallet\Feedback\BalanceFeedback;
use MultiWallet\Feedback\LaunchGameFeedback;
use MultiWallet\Feedback\MemberFeedback;
use MultiWallet\Feedback\SyncCallBackFeedback;
use MultiWallet\Feedback\TransferFeedback;
use MultiWallet\Params\LaunchGameParameter;
use MultiWallet\Params\MemberParameter;
use MultiWallet\Params\SyncCallBackParameter;
use MultiWallet\Params\SyncReportParameters;
use MultiWallet\Params\TransferParameter;

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
    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $agent = Member::where('member.id', $member->playerId)
            ->join('agent', 'agent.id', '=', 'alv5')
            ->select(DB::RAW("agent.account as account"))
            ->get()
            ->first();

        $params = [
            'mem'      => $member->username,
            'age'      => $agent->account,
            'function' => 'addMemberAccount',
        ];

        $memberFeedback = new MemberFeedback();
        $result         = $this->doSendProcess($memberFeedback, $params);

        if ($result instanceof MemberFeedback) {
            return $result;
        }

        if ($result->err) {
            $memberFeedback->extendParam = $result->ret;
        }

        $memberFeedback->error_msg = $this->errorMessage[$result->err_msg];

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

        $params = [
            'Credits'  => $transfer->amount,
            'logid'    => $transfer->billno,
            'mem'      => $transfer->member->username,
            'function' => 'doDeposit',
        ];

        $TransferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess($TransferFeedback, $params);

        if ($result instanceof TransferFeedback) {
            return $result;
        }

        if ($result->err) {
            $TransferFeedback->balance = $result->ret;
        }

        $TransferFeedback->error_msg = $this->errorMessage[$result->err_msg];

        return $TransferFeedback;
    }

    /**
     * 提款
     *
     * @param TransferParameter $transfer
     * @return MemberFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $params = [
            'Credits'  => $transfer->amount,
            'logid'    => $transfer->billno,
            'mem'      => $transfer->member->username,
            'function' => 'doWithdrawal',
        ];

        $TransferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess($TransferFeedback, $params);

        if ($result instanceof TransferFeedback) {
            return $result;
        }

        if ($result->err) {
            $TransferFeedback->balance = $result->ret;
        }

        $TransferFeedback->error_msg = $this->errorMessage[$result->err_msg];

        return $TransferFeedback;

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
            'mem'      => $launchGameParams->member->username, // 會員帳號
            'uip'      => $launchGameParams->member->ip, // 會員IP
        ];

        $launchGameFeedback = new LaunchGameFeedback();
        $result             = $this->doSendProcess($launchGameFeedback, $params);

        if ($result instanceof LaunchGameFeedback) {
            return $result;
        }

        // 資料錯誤
        if (!$result->err) {
            $launchGameFeedback->error_code = $result->err_msg;
            $launchGameFeedback->error_msg  = $this->errorMessage[$result->err_msg] ?? $result->err_msg;

            return $launchGameFeedback;
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
            'function' => 'getMemberPoint',
            'mem'      => $member->username,
        ];

        $feedback = new BalanceFeedback();
        $result   = $this->doSendProcess($feedback, $params);

        if ($result instanceof BalanceFeedback) {
            return $result;
        }

        // 資料錯誤
        if (!$result->err) {
            $feedback->error_code = $result->err_msg;
            $feedback->error_msg  = $this->errorMessage[$result->err_msg] ?? $result->err_msg;

            return $feedback;
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
    public function syncReport(SyncReportParameters $srp, callable $callback)
    {
        $start  = Carbon::parse($srp->startAt)->format('yyyy-MM-dd');
        $end    = Carbon::parse($srp->endAt)->format('yyyy-MM-dd');
        $params = [
            'function' => 'getDetailReport',
            'sdate'    => $start,
            'edate'    => $end,
            'page'     => 1,
        ];

        $callback($this->doSyncReport($params));
    }

    /**
     *
     *
     * @param \MultiWallet\Feedback\BaseFeedback $feedback
     * @param array $params
     * @return mix
     */
    private function doSendProcess($feedback, array $params)
    {
        $fullParams = $this->doParamsEncode($params);
        $response   = $this->post($this->config->apiUrl, $fullParams, false);
        // $result     = json_decode($response);

        // typeof $response is Object
        $result = $response;

        // 如果解不開，就直接把錯誤丟回去
        if ($response === false || $result === null) {
            $feedback->error_code = static::ENCRYPT_ERROR;
            $feedback->error_msg  = $response;

            return $feedback;
        }

        // 肯定出問題了
        if ($this->reponseCode != 200) {
            $feedback->error_code    = static::RESPONSE_ERROR;
            $feedback->response_code = $this->reponseCode;
            $feedback->error_msg     = '對方似乎報錯:' . $this->reponseCode;

            return $feedback;
        }

        return $result;
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

        // 將 secret 加至參數最後方
        $hash = implode("&", $params->toArray());
        $hash = md5($hash . $this->config->secret);
        $params->put('hash', $hash);

        return $params->all();
    }

    /**
     *
     *
     * @return array
     */
    private function doSyncReport($params)
    {
        $feedback = new SyncCallBackFeedback();
        $result   = $this->doSendProcess($feedback, $params);

        if ($result instanceof SyncCallBackFeedback) {
            return ($params['page'] === 1) ? $result : [];
        }

        // 資料錯誤
        if (!$result->err) {
            $feedback->error_code = $result->err_msg;
            $feedback->error_msg  = $this->errorMessage[$result->err_msg] ?? $result->err_msg;

            return ($params['page'] === 1) ? $feedback : [];
        }

        $result = $result->err_msg;
        $rows   = $result['report'];
        $data   = [];

        foreach ($rows as $row) {
            $data[] = $this->makeSyncCallBackParameter($row);
        }

        if ($result['allpage'] > $result['thispage']) {
            $params['page'] = $params['page'] + 1;
            $data           = array_merge($data, $this->doSyncReport($params));
        }

        return $data;
    }

    private function makeSyncCallBackParameter(array $row)
    {
        $callBackParam           = new SyncCallBackParameter();
        $callBackParam->mid      = $row['id']; // 注單ID
        $callBackParam->gameCode = '';
        $callBackParam->username = $row['mem_alias']; // "下注會員帳號
        $callBackParam->betAt    = $row['adddate']; // 下注時間(GMT+8)
        $callBackParam->reportAt = $row['settlementTime']; // 結算時間
        // $callBackParam->table = ;
        // $callBackParam->round = ;
        $callBackParam->content = $row['content'];
        // $callBackParam->waterAmount = ;
        $callBackParam->betAmount   = $row['gold']; // 下注時間金額
        $callBackParam->validAmount = $row['effective_gold']; // 有效下注
        $callBackParam->winAmount   = $row['wingold']; // 輸贏金額
        // $callBackParam->prize = ;
        // $callBackParam->tip = ;
        $callBackParam->ip     = $row['orderIP']; //下注IP
        $callBackParam->status = $row['result']; // 結果「W(贏)、L(輸)、LL(中分洞輸)、LW(中分洞贏)、N(取消比賽)、NC(取消注單)、0(尚無結果)」

        return $callBackParam;
    }
}
