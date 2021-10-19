<?php

namespace MultiWallet\Services;

use App\Events\MultiWalletStuckLog;
use App\Events\MultiWalletStuckLog;
use App\Events\ThrowException;
use App\Exceptions\ErrorException;
use App\Models\AgentPlatformConfig;
use App\Models\ClubRankConfig;
use App\Models\CompanyTotalReport;
use App\Models\FranchiseePlatformConfig;
use App\Models\Game;
use App\Models\GamePlatform;
use App\Models\LogMemberWallet;
use App\Models\LogProviderTransaction;
use App\Models\LogSyncReport;
use App\Models\Member;
use App\Models\MemberPlatformActive;
use App\Models\MemberWallet;
use App\Models\Report;
use App\Models\ReportDetail;
use DB;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MultiWallet\Api\BaseApi;
use MultiWallet\Base\BaseMultiWalletInterface;
use MultiWallet\Exceptions\FundsExceedException;
use MultiWallet\Exceptions\GameNotAllowedException;
use MultiWallet\Exceptions\SaveFailedException;
use MultiWallet\Exceptions\SyncException;
use MultiWallet\Feedback\SyncResultFeedback;
use MultiWallet\Params\LaunchGameParameter;
use MultiWallet\Params\MemberParameter;
use MultiWallet\Params\SyncCallBackParameter;
use MultiWallet\Params\SyncReportParameters;
use MultiWallet\Params\TransferParameter;

class MultiWalletService
{
    const GAME_OPTION_MULTI = 0;
    const GAME_OPTION_FIRST = 1;

    protected $baseMultiWalletApi = null;

    /**
     * GamePlatform.key.
     *
     * @var GamePlatform
     */
    protected $platform;

    // function __construct(BaseMultiWalletInterface $baseMultiWalletApi, string $key)
    // {
    //     $this->baseMultiWalletApi = $baseMultiWalletApi;

    //     $this->platform = GamePlatform::where('key', $key)->first();
    // }

    public function __construct(BaseMultiWalletInterface $baseMultiWalletApi, GamePlatform $platform)
    {
        $this->baseMultiWalletApi = $baseMultiWalletApi;

        $this->platform = $platform;
    }

    public function getGameList()
    {
        return $this->baseMultiWalletApi->getGameList();
    }

    /**
     * 建立使用者.
     *
     * @param MemberParameter $member
     * @param string $playerId
     * @return void
     */
    public function createMember(MemberParameter $memberParam, int $member_id)
    {
        $memberFeedback = $this->baseMultiWalletApi->createMember($memberParam);

        // 建立失敗的話
        if ($memberFeedback->error_code !== null) {
            // 發動紀錄事件
            event(new MultiWalletStuckLog($this->platform->id, $member_id, 'createMember', $memberFeedback->error_code, $memberFeedback->error_msg, 0));

            return false;
        }

        // 寫入資料
        if ($memberFeedback->extendParam !== null) {
            $active = MemberPlatformActive::where('platform_id', $this->platform->id)
                ->where('member_id', $member_id)
                ->first();

            // 如果沒建立的話...正常來說，會跑到這邊肯定沒有，不過還是保險先查一下
            if (! $active) {
                $active              = new MemberPlatformActive();
                $active->member_id   = $member_id;
                $active->platform_id = $this->platform->id;
                $active->enabled     = 1;
            }

            $active->username = $memberFeedback->extendParam;
            $active->saveOrError();
        }

        return true;
    }

    /**
     * 獲取使用者餘額.
     *
     * @param MemberParameter $member
     * @return void
     */
    public function getBalance(MemberParameter $member, int $member_id)
    {
        $balanceFeedback = $this->baseMultiWalletApi->getBalance($member);

        if ($balanceFeedback->error_code !== null || $balanceFeedback->response_code != 200) {
            event(new MultiWalletStuckLog($this->platform->id, $member_id, 'balance', $balanceFeedback->error_code, $balanceFeedback->error_msg, 0));

            return null;
        }

        return $balanceFeedback->balance;
    }

    /**
     * 存款.
     *
     * @param TransferParameter $transferParam
     * @param int $member_id
     * @param float $amount
     * @return void
     */
    public function deposit(TransferParameter $transferParam, int $member_id)
    {
        $amount = $transferParam->amount;
        // 檢查錢夠不夠
        $this->checkAmount($member_id, $amount);

        // 先問當前錢包有多少錢
        $balanceFeedback = $this->baseMultiWalletApi->getBalance($transferParam->member);
        if ($balanceFeedback === null) {
            // 一開始確定金額的時候就不回傳了，有問題！
            return false;
        }

        $oldBalance = $balanceFeedback->balance;

        // 與對方執行儲值動作
        $transferFeedback = $this->baseMultiWalletApi->deposit($transferParam);

        // 建立失敗
        if ($transferFeedback->error_code !== null || $transferFeedback->response_code != 200) {
            // 不可預期的，這時候先把錢卡著吧
            if ($transferFeedback->error_code == BaseApi::ENCRYPT_ERROR || $transferFeedback->error_code == BaseApi::RESPONSE_ERROR || $transferFeedback->error_code == BaseApi::UNKNOWN_ERROR) {
                // 發動紀錄事件
                event(new MultiWalletStuckLog($this->platform->id, $member_id, 'deposit', $transferFeedback->error_code, $transferFeedback->error_msg, $amount, MultiWalletStuckLog::PROCESS_STUCK, MultiWalletStuckLog::STATUS_STUCK));

                // 發動卡錢(再存入遊戲錢包這邊，只要有疑問，就先扣款)
                $this->modifyMemberWallet($member_id, -1 * $amount, '', LogMemberWallet::TYPE_TRANSFER_GAME);

                return false;
            }

            event(new MultiWalletStuckLog($this->platform->id, $member_id, 'deposit', $transferFeedback->error_code, $transferFeedback->error_msg, $amount));

            return false;
        }

        // 確保存款有真的完成再問一次
        $balanceFeedback = $this->baseMultiWalletApi->getBalance($transferParam->member);
        if ($balanceFeedback === null) {
            // 無法確定是不是正常，先卡錢再說
            // 發動紀錄事件、把錢卡著吧
            event(new MultiWalletStuckLog($this->platform->id, $member_id, 'deposit', $transferFeedback->error_code, $transferFeedback->error_msg, $amount, MultiWalletStuckLog::PROCESS_STUCK, MultiWalletStuckLog::STATUS_STUCK));

            // 發動卡錢(再存入遊戲錢包這邊，只要有疑問，就先扣款)
            $this->modifyMemberWallet($member_id, -1 * $amount, '', LogMemberWallet::TYPE_TRANSFER_GAME);

            return false;
        }

        $newBalance = $balanceFeedback->balance;

        // 錢進去的方式怪怪的，先扣使用者錢包的錢，列入紀錄，待人工查詢後決定是還錢還是正確扣款
        if (($newBalance - $oldBalance) != $amount) {
            // 發動紀錄事件、把錢卡著吧
            event(new MultiWalletStuckLog($this->platform->id, $member_id, 'deposit', $transferFeedback->error_code, $transferFeedback->error_msg, $amount, MultiWalletStuckLog::PROCESS_STUCK, MultiWalletStuckLog::STATUS_STUCK));

            // 發動卡錢(再存入遊戲錢包這邊，只要有疑問，就先扣款)
            $this->modifyMemberWallet($member_id, -1 * $amount, '', LogMemberWallet::TYPE_TRANSFER_GAME);

            return false;
        }

        $this->modifyMemberWallet($member_id, -1 * $amount, $transferFeedback->remote_payno, LogMemberWallet::TYPE_TRANSFER_GAME);

        return true;
    }

    /**
     * 提款.
     *
     * @param TransferParameter $transferParam
     * @param int $member_id
     * @param float $amount
     * @return void
     */
    public function withdraw(TransferParameter $transferParam, int $member_id)
    {
        $amount = $transferParam->amount;

        // 先問當前錢包有多少錢
        $balanceFeedback = $this->baseMultiWalletApi->getBalance($transferParam->member);
        if ($balanceFeedback === null) {
            // 一開始確定金額的時候就不回傳了，有問題！
            return false;
        }

        $oldBalance = $balanceFeedback->balance;

        // 查對方如果沒那麼多錢，不用做了，直接報錯
        if ($oldBalance < $amount) {
            return false;
        }

        // 向對方要求提款
        $transferFeedback = $this->baseMultiWalletApi->withdraw($transferParam);

        // 建立失敗
        if ($transferFeedback->error_code !== null || $transferFeedback->response_code != 200) {
            // 不可預期的，這時候先把錢卡著吧(提款只要不增加他的點數就是卡錢)
            if ($transferFeedback->error_code == BaseApi::ENCRYPT_ERROR || $transferFeedback->error_code == BaseApi::RESPONSE_ERROR || $transferFeedback->error_code == BaseApi::UNKNOWN_ERROR) {
                // 發動紀錄事件
                event(new MultiWalletStuckLog($this->platform->id, $member_id, 'withdraw', $transferFeedback->error_code, $transferFeedback->error_msg, $amount, MultiWalletStuckLog::PROCESS_STUCK, MultiWalletStuckLog::STATUS_STUCK));

                return false;
            }

            // 可預期的錯誤，不用卡
            event(new MultiWalletStuckLog($this->platform->id, $member_id, 'withdraw', $transferFeedback->error_code, $transferFeedback->error_msg, $amount));

            return false;
        }

        // 確保存款有真的完成再問一次
        $balanceFeedback = $this->baseMultiWalletApi->getBalance($transferParam->member);
        if ($balanceFeedback === null) {
            // 無法確定是不是正常，先卡錢再說
            // 發動紀錄事件、把錢卡著吧(提款只要不增加他的點數就是卡錢)
            event(new MultiWalletStuckLog($this->platform->id, $member_id, 'withdraw', $transferFeedback->error_code, $transferFeedback->error_msg, $amount, MultiWalletStuckLog::PROCESS_STUCK, MultiWalletStuckLog::STATUS_STUCK));

            return false;
        }

        $newBalance = $balanceFeedback->balance;

        // 錢出來的方式怪怪的，先不補點，列入紀錄，待人工查詢後決定是還錢還是不理
        if (($oldBalance - $newBalance) != $amount) {
            // 發動紀錄事件、把錢卡著吧(提款只要不增加他的點數就是卡錢)
            event(new MultiWalletStuckLog($this->platform->id, $member_id, 'withdraw', $transferFeedback->error_code, $transferFeedback->error_msg, $amount, MultiWalletStuckLog::PROCESS_STUCK, MultiWalletStuckLog::STATUS_STUCK));

            return false;
        }

        $this->modifyMemberWallet($member_id, $amount, $transferFeedback->remote_payno, LogMemberWallet::TYPE_TRANSFER_WALLET);

        return true;
    }

    /**
     * 發起遊戲登入，取回登入網址
     *
     * @param LaunchGameParameter $LGP
     * @param string $playerId
     * @return void
     */
    public function launchGame(LaunchGameParameter $LGP, int $member_id)
    {
        $active = $this->getPlatformActive($member_id);

        $member = Member::findOrError($member_id);

        $game = $this->getGames($LGP->gameId, static::GAME_OPTION_FIRST);

        /** @var AgentPlatformConfig $agentConfig */
        $agentConfig = AgentPlatformConfig::where('platform_id', $this->platform->id)
            ->where('agent_id', $member->alv5)
            ->first();

        if ($member->isDisabled()) {
            throw new GameNotAllowedException('member disabled');
        }

        if (! $game) {
            throw new GameNotAllowedException('game not found');
        }

        if ($game->isDisabled() || $game->isMaintain()) {
            throw new GameNotAllowedException('game is disabled or maintain');
        }

        // 找不到代理設定
        if (! $agentConfig) {
            throw new GameNotAllowedException('config not found');
        }

        // 代理禁用遊戲 or 會員被禁止登入遊戲
        if ($agentConfig->isDisabled() || $active->isDisabled()) {
            throw new GameNotAllowedException('member not allowed');
        }

        /** @var ClubRankConfig $clubRankConfig */
        $clubRankConfig = ClubRankConfig::where('club_id', $member->club_id)
            ->where('club_rank_id', $member->club_rank_id)
            ->where('game_id', $game->id)
            ->first();

        // 未設定俱樂部的遊戲退水等資料
        if (! $clubRankConfig) {
            throw new GameNotAllowedException(__('provider.no-club-rank-config'));
        }

        /** @var FranchiseePlatformConfig $fconf */
        $fconf = FranchiseePlatformConfig::where('platform_id', $this->platform->id)
            ->where('franchisee_id', $member->franchisee_id)
            ->first();

        // 未設定加盟商的平台參數
        if (! $fconf) {
            throw new GameNotAllowedException(__('provider.no-franchisee-config'));
        }

        /**
         * 查詢會員組織層設定.
         * @var AgentPlatformConfig[] $agents
         */
        $numAgents = AgentPlatformConfig::whereIn('id', $member->parentIds())->count();

        if ($numAgents !== 5) {
            throw new GameNotAllowedException(__('provider.parent-agents-error'));
        }

        // 建立 player_id
        if (! $active->player_id) {
            throw new ErrorException('不正確登入程序 (player id 不存在');
        }

        $active->loggedin_at = date('Y-m-d H:i:s');
        $active->save();

        $launchGameFeedback = $this->baseMultiWalletApi->launchGame($LGP);

        if ($launchGameFeedback->error_code !== null || $launchGameFeedback->response_code != 200) {
            event(new MultiWalletStuckLog($this->platform->id, $member_id, 'launchGame', $launchGameFeedback->error_code, $launchGameFeedback->error_msg, 0));

            return false;
        }

        return $launchGameFeedback;
    }

    /**
     * 同步作業.
     *
     * @param SyncReportParameters $srp
     * @return void
     */
    public function syncReport(SyncReportParameters $srp)
    {
        return $this->baseMultiWalletApi->syncReport($srp, function ($rows) use ($srp) {
            return $this->syncCallBack($rows, $srp);
        });
    }

    /**
     * 同步回調，由api連接端，整理為可讀格式，直接讀取塞入資料庫.
     *
     * @return void
     */
    protected function syncCallBack($rows, SyncReportParameters $srp)
    {
        if ($rows instanceof SyncCallBackFeedback) {
            event(new MultiWalletStuckLog($this->platform->id, 0, 'syncCallBack', $rows->error_code, $rows->error_msg, 0));

            throw new SyncException('error when sync report please check log!');
        }

        // 先整理資訊，減低資料庫壓力
        // 注單編號
        $mids = [];
        // 遊戲編號(遊戲方)
        $codes = [];
        // 玩家 id
        $playerIds = [];
        foreach ($rows as $row) {
            $mids[]      = $row->mid;
            $codes[]     = $row->gameCode;
            $playerIds[] = $row->username;
        }

        $codes     = array_unique($codes);
        $playerIds = array_unique($playerIds);

        // 獲取遊戲
        $games = $this->getGames($codes, static::GAME_OPTION_MULTI);

        // 獲取玩家資訊
        $players = MemberPlatformActive::select('member_id', 'player_id')
            ->with([
                'member' => function (HasOne $hasone) {
                    return $hasone->select([
                        'id',
                        'franchisee_id',
                        'club_rank_id',
                        'alv1',
                        'alv2',
                        'alv3',
                        'alv4',
                        'alv5',
                        'mlv1',
                        'mlv2',
                        'mlv3',
                    ]);
                },
            ])
            ->where('platform_id', $this->platform->id)
            ->whereIn('player_id', $playerIds)
            ->get()
            ->keyBy('player_id');

        // 獲取加盟主設定
        $fconfigs = FranchiseePlatformConfig::where('platform_id', $this->platform->id)
            ->whereIn('franchisee_id', $players->pluck('member.franchisee_id')->all() ?: [0])
            ->get()
            ->keyBy('franchisee_id');

        // 獲取俱樂部資訊
        /** @var ClubRankConfig[] $crcs */
        $crcs = ClubRankConfig::select('club_rank_id', 'game_id', 'water_percent')
            ->whereIn('game_id', $games->pluck('id') ?: [0])
            ->whereIn('club_rank_id', $players->pluck('member.club_rank_id')->all())
            ->get();

        $rankConfigs = [];
        foreach ($crcs as $row) {
            $rankConfigs["{$row->club_rank_id}-{$row->game_id}"] = $row;
        }

        // 取得上層所有上層
        $aids = $players->map(function ($p) {
            return $p->member->parentIds();
        })
            ->collapse()
            ->all();
        $agconfigs = AgentPlatformConfig::select('agent_id', 'percent', 'water_percent', 'bonus_percent')
            ->whereIn('agent_id', $aids)
            ->get()
            ->keyBy('agent_id');

        $memParents = [];

        // 取得關聯報表
        /** @var Report[] $reports */
        $reports = Report::with([
            'detail',
            'member' => function (HasOne $hasone) {
                return $hasone->select([
                    'id',
                    'franchisee_id',
                    'club_rank_id',
                    'alv1',
                    'alv2',
                    'alv3',
                    'alv4',
                    'alv5',
                    'mlv1',
                    'mlv2',
                    'mlv3',
                ]);
            },
        ])
        ->where('platform_id', $this->platform->id)
        ->whereIn('mid', $mids)
        ->get()
        ->keyBy('mid');

        $result                = new SyncResultFeedback();
        $result->total         = 0;
        $result->num_completes = 0;
        $result->num_fails     = 0;
        $fails                 = [];
        foreach ($rows as $row) {
            $result->total += 1;

            $player = $players[$row->username];
            $member = $player->member;

            $game = $games[$row->gameCode] ?? null;
            if (! $game) {
                throw new ErrorException("game not found. code={$row->gameCode}");
            }

            $report = $reports[$row->mid] ?? null;
            $detail = $report ? $report->detail : null;

            if (! $report) {
                // 如果沒有報表，就要建立一個
                $key      = "{$member->club_rank_id}-{$game->id}";
                $rankConf = $rankConfigs[$key] ?? null;
                if ($rankConf === null) {
                    throw new ErrorException("club_rank_config not found. key={$key}");
                }

                $fconf = $fconfigs[$member->franchisee_id] ?? null;
                if (! $fconf) {
                    throw new ErrorException("franchisee_platform_config not found. fid={$member->franchisee_id}");
                }

                $parents = $memParents[$member->id] ?? null;
                if (! $parents) {
                    $member  = $player->member;
                    $parents = [];
                    for ($lv = 1; $lv <= 5; $lv++) {
                        $parents[] = $agconfigs[$player->member->{"alv{$lv}"}];
                    }
                    $memParents[$member->id] = $parents;
                }

                [$report, $detail] = $this->createReport($member, $game, $fconf, $rankConf, $parents);
            }

            $report->mid                   = $row->mid;
            $report->bet_amount            = $row->betAmount;
            $report->bet_at                = $row->betAt;
            $report->report_at             = $row->reportAt;
            $report->valid_amount          = $row->validAmount;
            $report->win_amount            = $row->winAmount;
            $report->prize                 = $row->prize;
            $report->tip                   = $row->tip;
            $report->status                = $row->status;
            $report->provider_water_amount = $row->waterAmount ?: 0;

            $detail->table     = $row->table;
            $detail->round     = $row->round;
            $detail->content   = $row->content;
            $detail->report_at = $row->reportAt;
            $detail->ip        = $row->ip;

            try {
                $report->updateTotal($detail);

                DB::beginTransaction();

                $report->saveOrError();

                $detail->id = $report->id;
                $detail->saveOrError();

                // 寫入金額總報
                $companyTotalReport = new CompanyTotalReport();
                $companyTotalReport->add($report);

                DB::commit();
                $result->num_completes += 1;
            } catch (Exception | \Throwable | \ErrorException $err) {
                DB::rollBack();
                event(new ThrowException($err));
                $result->num_fails += 1;
                $fails[] = ['message' => $err->getMessage(), 'row' => $row];
            }

            // 進行同步作業的報表異動
            // $this->syncRowProcess($row, $report, $detail);
        }
        $result->status = $result->num_fails ? LogSyncReport::STATUS_FAILED : LogSyncReport::STATUS_COMPLETED;
        $result->fails  = $fails;

        $log                = new LogSyncReport();
        $log->platform_id   = $this->platform->id;
        $log->total         = $result->total;
        $log->num_completes = $result->num_completes;
        $log->num_fails     = $result->num_fails;
        $log->stime         = $srp->startAt;
        $log->etime         = $srp->endAt;
        $log->fails         = $result->fails;
        $log->message       = [];
        $log->status        = $result->status;
        $log->saveOrError();

        return $result;
    }

    protected function syncRowProcess(SyncCallBackParameter $SCBP, $report, $detail)
    {
        $report->mid                   = $SCBP->mid;
        $report->bet_amount            = $SCBP->betAmount;
        $report->bet_at                = $SCBP->betAt;
        $report->report_at             = $SCBP->reportAt;
        $report->valid_amount          = $SCBP->validAmount;
        $report->win_amount            = $SCBP->winAmount;
        $report->prize                 = $SCBP->prize;
        $report->tip                   = $SCBP->tip;
        $report->status                = $SCBP->status;
        $report->provider_water_amount = $SCBP->waterAmount ?: 0;

        $detail->table     = $SCBP->table;
        $detail->round     = $SCBP->round;
        $detail->content   = $SCBP->content;
        $detail->report_at = $SCBP->reportAt;
        $detail->ip        = $SCBP->ip;

        DB::transaction(function () use ($report, $detail) {
            $report->updateTotal($detail);

            if (! $report->save()) {
                throw new SaveFailedException('report');
            }

            $detail->id = $report->id;
            if (! $detail->save()) {
                throw new SaveFailedException('report-detail');
            }
        });
    }

    /**
     * 取得玩家 MemberPlatformActive 資料.
     *
     * @param int $member_id
     * @return MemberPlatformActive
     */
    private function getPlatformActive(int $member_id)
    {
        $active = MemberPlatformActive::where('platform_id', $this->platform->id)
            ->where('member_id', $member_id)
            ->first();

        if (! $active) {
            throw new ErrorException('player not found : '.$member_id.','.$this->platform->id);
        }

        return $active;
    }

    /**
     * 檢查錢是不是夠.
     *
     * @param int $mid
     * @param float $amount
     * @return void
     */
    private function checkAmount(int $mid, float $amount)
    {
        $wallet = MemberWallet::find($mid);
        if (! $wallet) {
            throw new ErrorException('wallet not found');
        }

        if ($wallet->money < $amount) {
            throw new FundsExceedException(__('provider.found-exceed'));
        }
    }

    /**
     * 修改錢包金額.
     *
     * @param int $member_id
     * @param float $amount
     * @param string $comment
     * @param string $type
     * @return void
     */
    private function modifyMemberWallet(int $member_id, float $amount, string $comment, string $type)
    {
        $memberWalletLog               = new LogMemberWallet();
        $memberWalletLog->member_id    = $member_id;
        $memberWalletLog->type         = $type;
        $memberWalletLog->type_id      = $this->platform->id;
        $memberWalletLog->change_money = $amount;

        DB::transaction(function () use ($memberWalletLog, $comment) {
            $this->logTransaction(0, $comment, $memberWalletLog);
        });
    }

    /**
     * 更新交易記錄.
     *
     * @param int $rid 報表 id (report.id)
     * @param string $tid 遊戲方提供的交易 id
     * @param LogMemberWallet $logWallet
     * @return void
     */
    private function logTransaction(int $rid, string $tid, LogMemberWallet $logWallet)
    {
        try {
            $logWallet->saveRecord();
        } catch (Exception $err) {
            event(new ThrowException($err));
            throw new SaveFailedException('log-member-wallet');
        }
        $logTransaction                 = new LogProviderTransaction();
        $logTransaction->platform_id    = $this->platform->id;
        $logTransaction->report_id      = $rid;
        $logTransaction->transaction_id = $tid;
        $logTransaction->log_id         = $logWallet->id;
        if (! $logTransaction->save()) {
            throw new SaveFailedException('log-provider-transaction');
        }
    }

    /**
     * 產生 report.
     *
     * @param string $mid 註單編號
     * @param Member $member
     * @param Game $game 部分遊戲商下注的時後不會帶 game code, 只能 sync report 再更新
     * @param FranchiseePlatformConfig $fconf 會員所屬加盟商設定
     * @param ClubRankConfig $crconf 會員所屬俱樂部設定
     * @param AgentPlatformConfig[] $agconfigs 會員上層代理設定
     * @return void
     */
    private function createReport(
        Member $member,
        Game $game = null,
        FranchiseePlatformConfig $fconf = null,
        ClubRankConfig $crconf = null,
        $agconfigs = null
    ) {
        $platform = $this->platform;

        /** @var FranchiseePlatformConfig $fconf */
        $fconf = $fconf ?: FranchiseePlatformConfig::where('platform_id', $platform->id)
            ->where('franchisee_id', $member->franchisee_id)
            ->first();

        if (! $fconf) {
            throw new ErrorException("franchisee_platform_config not found. pid={$platform->id}, fid={$member->franchisee_id}");
        }

        if ($game) {
            $crconf = $crconf ?: ClubRankConfig::where('club_rank_id', $member->club_rank_id)
                ->where('game_id', $game->id)
                ->first();

            if (! $crconf) {
                throw new ErrorException("club_rank_config not found. crid={$member->club_rank_id}, gid={$game->id}");
            }
        }

        $agconfigs = $agconfigs ?: AgentPlatformConfig::select('agent_id', 'percent', 'water_percent', 'bonus_percent')
            ->whereIn('agent_id', $member->parentIds())
            ->where('platform_id', $platform->id)
            ->get();
        $agconfigs = collect($agconfigs)->keyBy('agent_id');

        $report                = new Report();
        $report->franchisee_id = $member->franchisee_id;
        $report->member_id     = $member->id;
        $report->platform_id   = $platform->id;
        $report->game_id       = $game->id ?? 0;
        $report->type          = $game->type ?? 'undefined';
        $report->status        = Report::STATUS_COMPLETED;
        $report->bet_amount    = 0;
        $report->valid_amount  = 0;
        $report->win_amount    = 0;
        $report->prize         = 0;
        $report->tip           = 0;
        $report->report_at     = '';
        $report->bet_at        = '';
        $report->total         = 0;
        $report->settle_at     = null;
        $report->cancel_at     = null;

        $detail                      = new ReportDetail();
        $detail->report_at           = '';
        $detail->origin_valid_amount = 0;

        // 遊戲方退水
        // $report->provider_water_amount = floatval($bet->waterAmount);

        // 代理退水提撥比
        $detail->allocate_agent_water_percent = $fconf->allocate_agent_water_percent;

        // 代理紅利提額度
        $detail->allocate_agent_bonus_percent = $fconf->allocate_agent_bonus_percent;

        // 會員退水提撥比 (部分遊戲商可能在下注的時後不會給 game code, 所以會員退水比可能會是 0)
        $detail->allocate_member_water_percent = $crconf->water_percent ?? 0;
        $report->company_percent               = 100 - $fconf->percent;
        $detail->company_water_percent         = 100 - $fconf->water_percent;
        $detail->company_bonus_percent         = 100 - $fconf->bonus_percent;

        /**
         * 各佔成的「實佔」計算 = (上層下放值 - 本層下放值)
         * 例：
         *  加盟商下放 90 => 實際佔成 10% (100 - 90)
         *  lv 1 下放 75 =>  實際佔成 15% (90 - 75).
         */
        $prevPercent      = $fconf->percent;
        $prevWaterPercent = $fconf->water_percent;
        $prevBonusPercent = $fconf->bonus_percent;

        for ($lv = 1; $lv <= 5; $lv++) {
            $agconf = $agconfigs[$member->{"alv{$lv}"}];
            if (! $agconf) {
                throw new ErrorException("agent_platform_config not found. pid={$platform->id} aid = ".$member->{"alv{$lv}"});
            }

            $report->{"alv{$lv}"} = $agconf->agent_id;

            $report->{"alv{$lv}_percent"} = $prevPercent - $agconf->percent;

            $detail->{"alv{$lv}_water_percent"} = $prevWaterPercent - $agconf->water_percent;

            $detail->{"alv{$lv}_bonus_percent"} = $prevBonusPercent - $agconf->bonus_percent;

            $prevPercent      = $agconf->percent;
            $prevWaterPercent = $agconf->water_percent;
            $prevBonusPercent = $agconf->bonus_percent;
        }

        // 若各層未佔滿, 多的佔成要後丟給加盟主
        $report->alv1_percent += $prevPercent ?: 0;
        $detail->alv1_water_percent += $prevWaterPercent ?: 0;
        $detail->alv1_bonus_percent += $prevBonusPercent ?: 0;
        // 設定上 3 層會員的紅利分配比, 若不存在, 則為 0
        $report->mlv1               = $member->mlv1;
        $report->mlv2               = $member->mlv2;
        $report->mlv3               = $member->mlv3;
        $detail->mlv1_bonus_percent = $report->mlv1 ? $fconf->mlv1_bonus_percent : 0;
        $detail->mlv2_bonus_percent = $report->mlv2 ? $fconf->mlv2_bonus_percent : 0;
        $detail->mlv3_bonus_percent = $report->mlv3 ? $fconf->mlv3_bonus_percent : 0;

        return [$report, $detail];
    }

    private function getGames($codes, int $game_option)
    {
        // 先查是不是測試遊戲，是的話要抓測試遊戲
        $platformId = $this->platform->id;
        if ($this->platform->fun === 1) {
            $platformId = $this->platform->platformId;
        }

        $games = Game::where('platform_id', $platformId);

        // 因為有可能只查單個遊戲，所以檢查是不是陣列
        if (is_array($codes)) {
            $games->whereIn('code', $codes);
        } else {
            $games->where('code', $codes);
        }

        if ($game_option == static::GAME_OPTION_MULTI) {
            return $games->get()->keyBy('code');
        }

        if ($game_option == static::GAME_OPTION_FIRST) {
            return $games->first();
        }

        throw new ErrorException('game option not found');
    }
}
