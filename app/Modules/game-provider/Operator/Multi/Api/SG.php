<?php

namespace GameProvider\Operator\Multi\Api;

use App\Exceptions\FailException;
use App\Models\Agent;
use App\Models\Member;
use App\Models\MemberPlatformActive;
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
use GameProvider\Operator\Multi\Config\SGConfigConstruct;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
// use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;

class SG extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    private $error_message = [
        'E01' => '帳號已存在',
        'E02' => '上層帳號未創建',
        'E03' => '帳號新增失敗',
        'E04' => '詳細資料不正確',
        'E05' => 'ext會員新增失敗',
        'E06' => 'ext會員額度資料新增失敗',
        'E07' => 'ext會員組織線錯誤',
        'E08' => '會員不存在',
        'E09' => '動作錯誤 (存提',
        'E10' => 'LOG寫入失敗(存提',
        'E11' => '金額不足',
        'E12' => '參數不完全',
        'E13' => '站台',
        'E14' => 'LogId重複',
    ];

    public function __construct(array $config)
    {
        $this->config             = new SGConfigConstruct();
        $this->config->apiUrl     = $config['apiUrl'];
        $this->config->station    = $config['station'];
        $this->config->rootLevel  = $config['rootLevel'];
        $this->config->is_t1      = $config['t1'];
    }

    public function getGameList()
    {
    }

    public function launchAdmin($playerId, $lv)
    {
        $params = [
            'acc'     => $playerId,
            'lv'      => $lv,
        ];

        $result             = $this->doSendProcess('EXT_LOGIN', $params);

        if ($result->status !== 1) {
            throw new LaunchGameException(get_class($this), 'launch game error! error code : '.$result->status.' '.$this->error_message[$result->message], $result->message);
        }

        return $result->data;
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
        $mb = Member::where('member.id', $member->member_id)
            ->first();

        $parents = Agent::select('account', 'name', 'level')
            ->whereIn('id', $mb->parentIds())
            ->orderBy('level', 'ASC')
            ->get();

        $rootLevel = $this->config->rootLevel;
        $acc_array = [];

        if ($this->config->is_t1) {
            if ($mb->alv3 == 171) {
                // 哲測股東例外
                $acc_array[] = [
                    'acc'   => 'I1220374',
                    'name'  => '正式線',
                    'lv'    => 1,
                    'extra' => [],
                ];
                $acc_array[] = [
                    'acc'   => 'I2101931',
                    'name'  => '正式線',
                    'lv'    => 2,
                    'extra' => [],
                ];
            } else {
                $acc_array[] = [
                    'acc'   => 'as003',
                    'name'  => '正式線',
                    'lv'    => 1,
                    'extra' => [],
                ];
                $acc_array[] = [
                    'acc'   => 'as004',
                    'name'  => '正式線',
                    'lv'    => 2,
                    'extra' => [],
                ];
            }
        }

        foreach ($parents as $parent) {
            if ($parent->level <= $rootLevel) {
                continue;
            }
            $acc_array[] = [
                'acc'   => $parent->account,
                'name'  => $parent->name,
                'lv'    => $parent->level,
                'extra' => [],
            ];
        }

        $acc_array[] = [
            'acc'   => $member->playerId,
            'name'  => $member->playerId,
            'lv'    => 6,
            'extra' => [],
        ];

        // 預設層級
        $params = [
            'acc' => $acc_array,
        ];

        $result = $this->doSendProcess('EXT_ADD', $params);

        if ($result->status !== 1) {
            throw new CreateMemberException(get_class($this), 'create member error! error code : '.$result->status.' '.$this->error_message[$result->message], $result->message);
        }

        $memberFeedback              = new MemberFeedback();
        $memberFeedback->extendParam = $member->playerId;

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
        $payno = $transfer->member->member_id.time();

        $params = [
            'acc'     => $transfer->member->playerId,
            'gold'    => $transfer->amount,
            'lv'      => 6,
            'type'    => 'in',
            'logId'   => $payno,
        ];

        $transferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess('EXT_BANK', $params);

        if ($result->status !== 1) {
            throw new TransferException(
                get_class($this),
                $result->message.' '.$this->error_message[$result->message],
                $result->message.' '.$this->error_message[$result->message]
            );
        }

        $transferFeedback->remote_payno = $result->data;

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
        $payno = $transfer->member->member_id.time();

        $params = [
            'acc'     => $transfer->member->playerId,
            'gold'    => $transfer->amount,
            'lv'      => 6,
            'type'    => 'out',
            'logId'   => $payno,
        ];

        $transferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess('EXT_BANK', $params);

        if ($result->status !== 1) {
            throw new TransferException(
                get_class($this),
                $result->message.' '.$this->error_message[$result->message],
                $result->message.' '.$this->error_message[$result->message]
            );
        }

        $transferFeedback->remote_payno = $result->data;

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
            'acc'     => $launchGameParams->member->playerId,
            'lv'      => 6,
        ];

        $launchGameFeedback = new LaunchGameFeedback();
        $result             = $this->doSendProcess('EXT_LOGIN', $params);

        if ($result->status !== 1) {
            throw new LaunchGameException(get_class($this), 'launch game error! error code : '.$result->status.' '.$this->error_message[$result->message], $result->message);
        }

        $launchGameFeedback->gameUrl       = $result->data;
        $launchGameFeedback->mobileGameUrl = $result->data; // 不確定是否要手機連結

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
            'acc'     => [$member->playerId],
            'lv'      => 6,
        ];

        $feedback = new BalanceFeedback();
        $result   = $this->doSendProcess('EXT_GET_QUOTA', $params);

        if ($result->status !== 1) {
            throw new BalanceException(get_class($this), 'get balance error! error code : '.$result->status.' '.$this->error_message[$result->message], $result->message);
        }

        $feedback->balance = $result->data->{$member->playerId};

        return $feedback;
    }

    public function getInfo($date)
    {
        // 取回期數資料
        $params = [
            'date' => $date,
        ];
        $result = $this->doSendProcess('EXT_CASINO_INFO', $params);

        if ($result->status !== 1) {
            throw new FailException('get info error! error code : '.$result->status.' '.$this->error_message[$result->message]);
        }

        $rows    = $result->data;
        // 之前的回傳法不好, 應該整包丟回去
        $gids = [];
        foreach ($rows as $row) {
            if ($row->result == 'Y') {
                $gids[] = $row->gid;
            }
        }

        return $gids;

        // 只要當天資料有開獎，就傳回
        // $lottery = 'N';
        // $start   = '';
        // $end     = '';
        // $rows    = $result->data;
        // foreach ($rows as $row) {
        //     if ($row->result == 'Y') {
        //         $lottery = 'Y';
        //         $start   = $row->start;
        //         $end     = $row->end;
        //         break;
        //     }
        // }

        // return [
        //     'result' => $lottery,
        //     'start'  => $start,
        //     'end'    => $end,
        // ];
    }

    public function getBetAccount($start, $end)
    {
        $params = [
            'start' => $start,
            'end'   => $end,
        ];

        $result = $this->doSendProcess('EXT_REPORT_MEMBER', $params);

        if ($result->status !== 1) {
            throw new FailException('get bet account error! error code : '.$result->status.' '.$this->error_message[$result->message]);
        }

        return $result->data;
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
        $start  = Carbon::parse($srp->startAt)->format('Y-m-d');
        $end    = Carbon::parse($srp->endAt)->format('Y-m-d');

        // 拿開盤時間 和是否開盤了
        $gids = $this->getInfo($start);

        // 多拿一天, 確保拿到加州彩
        $gids = array_merge($gids, $this->getInfo(Carbon::parse($srp->startAt)->addDays(1)->format('Y-m-d')));

        // 先把有投注的會員找出來
        $account_array = $this->getBetAccount($start, $end);

        if ($account_array && count($account_array) == 0) {
            // 沒東西，直接返回
            return $callback([]);
        }

        return $callback($this->doSyncReport($start, $end, $account_array, 0, $gids));
    }

    /**
     * 同步注單(兌獎).
     *
     * @param \MultiWallet\Params\SyncReportParameters $srp
     * @param int $platform_id
     * @param callable $callback
     * @return \MultiWallet\Feedback\SyncCallBackFeedback
     */
    public function syncReportLottery(SyncReportParameter $srp, int $platform_id, callable $callback)
    {
        // 棄用
        // $start = Carbon::parse($srp->startAt)->format('Y-m-d');
        // $end   = Carbon::parse($srp->endAt)->format('Y-m-d');

        // // 拿開盤時間 和是否開盤了
        // $info = $this->getInfo($start);

        // // 未開盤
        // if ($info['result'] === 'N') {
        //     return $callback([]);
        // }

        // // 把資料庫有投注的人都撈出來
        // $reports = Report::select('member_id')
        //     ->where('platform_id', $platform_id)
        //     ->where('report_at', '>=', $info['start'])
        //     ->where('report_at', '<=', $info['end'])
        //     // ->where('status', Report::STATUS_BETTING) // 應該要都撈, 不然會被覆蓋資訊, 而且對過萬一有改不會再改
        //     ->groupBy('member_id')
        //     ->get();

        // $memberIds = [];
        // foreach ($reports as $report) {
        //     $memberIds[] = $report->member_id;
        // }

        // // 把id換成player_id
        // $platformActives = MemberPlatformActive::select('player_id')->where('platform_id', $platform_id)->whereIn('member_id', $memberIds)->get();

        // $members = [];
        // foreach ($platformActives as $platformActive) {
        //     $members[] = $platformActive->player_id;
        // }

        // if (count($members) == 0) {
        //     // 沒東西，直接返回
        //     return $callback([]);
        // }

        // return $callback($this->doSyncReport($start, $end, $members, 0, Report::STATUS_COMPLETED));
    }

    /**
     * @param \MultiWallet\Feedback\BaseFeedback $feedback
     * @param string $code
     * @param array $params
     * @return mix
     */
    private function doSendProcess(string $code, array $params)
    {
        $fullParams = $this->setParams($code, $params);
        $response   = $this->post($this->config->apiUrl, json_encode($fullParams), true);

        return $response;
    }

    public function setParams(string $code, array $params)
    {
        $tmp_params            = $params;
        $tmp_params['station'] = $this->config->station;

        return [
            'code'   => $code,
            'parame' => $tmp_params,
        ];
    }

    /**
     * @return array
     */
    private function doSyncReport($start, $end, $account_array, $key, $gids = [])
    {
        // $feedback = new SyncCallBackFeedback();
        $account = $account_array[$key];

        // 沒單的時候有時候會給空的, 要跳過
        if (! $account) {
            return [];
        }

        $params  = [
            'acc'     => $account,
            'lv'      => 6,
            'start'   => $start,
            'end'     => $end,
        ];

        $result   = $this->doSendProcess('EXT_REPORT_DTL3', $params);

        if ($result->status !== 1) {
            throw new SyncException(get_class($this), 'sync error! error code : '.$result->status.' '.$this->error_message[$result->message], $result->message);
        }

        $rows   = $result->data;
        $data   = [];

        // 無言，當時間不存在期數時，竟然給字串
        if (! is_array($rows)) {
            return $data;
        }

        foreach ($rows as $row) {
            $data[] = $this->makeSyncCallBackParameter($account, $row, $gids);
        }

        $next_key = $key + 1;

        if (count($account_array) > $next_key) {
            $data = array_merge($data, $this->doSyncReport($start, $end, $account_array, $next_key, $gids));
        }

        return $data;
    }

    private function makeSyncCallBackParameter($account, $row, $gids = [])
    {
        $callBackParam           = new SyncCallBackParameter();
        $callBackParam->mid      = $row->bid.'-'.$row->sub; // 注單ID
        $callBackParam->gameCode = 'SG';
        $callBackParam->username = strtolower($account); // "下注會員帳號
        $callBackParam->betAt    = $row->time; // 下注時間(GMT+8)
        $callBackParam->reportAt = $row->time; // 結算時間 改成投注時間，不然會查不到
        $callBackParam->table    = $row->cs;
        $callBackParam->round    = $row->gid;
        $callBackParam->content  = [
            'cs'      => $row->cs,
            'play'    => $row->play,
            'type'    => $row->type ?? '',
            'content' => $row->content,
        ];
        // $callBackParam->waterAmount = ;
        $callBackParam->betAmount   = $row->tm; // 下注時間金額
        $callBackParam->validAmount = $row->tm - $row->wt; // 有效下注
        $callBackParam->winAmount   = $row->tm - $row->wl; // 輸贏金額
        // $callBackParam->prize = ;
        // $callBackParam->tip = ;
        // $callBackParam->ip     = $row->orderIP; //下注IP

        // $callBackParam->status = $status;
        $callBackParam->status = Report::STATUS_BETTING;

        // 決定是否開獎
        if (in_array($row->gid, $gids)) {
            $callBackParam->status = Report::STATUS_COMPLETED;
        }

        // 撤單
        if ($row->off == 'Y') {
            $callBackParam->status      = Report::STATUS_CANCEL;
            // SG回傳不會變動數值，故需要自己計算數值
            $callBackParam->winAmount   = $row->tm;
            $callBackParam->validAmount = 0;
        }

        return $callBackParam;
    }
}
