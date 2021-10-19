<?php

namespace GameProvider\Services;

use App\Events\MultiWalletStuckLog;
use App\Events\ThrowException;
use App\Exceptions\ErrorException;
use App\Exceptions\ForbiddenException;
use App\Models\AgentPlatformConfig;
use App\Models\ClubRankConfig;
use App\Models\FranchiseePlatformConfig;
use App\Models\Game;
use App\Models\GamePlatform;
use App\Models\LogMemberWallet;
use App\Models\LogProviderTransaction;
use App\Models\LogSyncReport;
use App\Models\Member;
use App\Models\MemberPlatformActive;
use App\Models\MemberWallet;
use App\Models\QuestReward;
use App\Models\Report;
use App\Models\ReportDetail;
use App\Models\ReportPrize;
use App\Services\ActivityWallet\ActivityWalletService;
use App\Services\Franchisee\FranchiseeWaterMultipleService;
use App\Services\Provider\AccessService;
use App\Services\Quest\QuestCombineService;
use App\Services\Quest\QuestService;
use Carbon\Carbon;
use DB;
use GameProvider\Exceptions\FundsExceedException;
use GameProvider\Exceptions\GameNotAllowedException;
use GameProvider\Exceptions\SaveFailedException;
use GameProvider\Operator\Feedback\SyncResultFeedback;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;
// use Illuminate\Support\Facades\Artisan;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Ramsey\Uuid\Uuid;

class BaseWalletService
{
    /**
     * GamePlatform.key.
     *
     * @var GamePlatform
     */
    protected $platform = null;

    /**
     * 遊戲代碼
     *
     * @var string
     */
    protected $key = null;

    /**
     * 交易紀錄.
     *
     * @var LogProviderTransaction
     */
    protected $logProvider = null;

    protected $inputData = null;

    /**
     * 抓取遊戲的方式（多個 or 一個）.
     */
    const GAME_OPTION_MULTI = 0;
    const GAME_OPTION_FIRST = 1;

    /**
     * 把input拿回來.
     *
     * @param array $data
     * @return void
     */
    protected function initInputData($data = null)
    {
        $method      = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? trim($_SERVER['CONTENT_TYPE']) : '';

        if ($data) {
            $this->inputData = $data;
        } elseif ($method === 'POST') {
            $this->inputData = strcasecmp($contentType, 'application/json') != 0
                ? ($_POST ?? [])
                : json_decode(file_get_contents('php://input'), true);
        } else {
            $this->inputData = $_GET ?: [];
        }
    }

    /**
     * 分析陣列內容回傳.
     *
     * @param array $target
     * @param string $key
     * @param string $default
     * @return void
     */
    protected function arrayGet($target, $key = null, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($target)) {
                if (! array_key_exists($segment, $target)) {
                    return $default;
                }
                $target = $target[$segment];
            } elseif ($target instanceof ArrayAccess) {
                if (! isset($target[$segment])) {
                    return $default;
                }
                $target = $target[$segment];
            } elseif (is_object($target)) {
                if (! isset($target->{$segment})) {
                    return $default;
                }
                $target = $target->{$segment};
            } else {
                return $default;
            }
        }

        return $target;
    }

    /**
     * 直接取input值
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function input($key = null, $default = null)
    {
        if (function_exists('request') && get_class(request()) === 'Illuminate\Http\Request') {
            return request()->input($key, $default);
        } else {
            if ($this->inputData === null) {
                $this->initInputData();
            }

            return $this->arrayGet($this->inputData, $key, $default);
        }
    }

    /**
     * 確定是否需要生成使用者帳號，如需要則產生一組.
     *
     * 下一個步驟必須確定對方是不是成功，不成功，記得把資料給刪了
     *
     * @return MemberPlatformActive
     */
    protected function createMemberToDB()
    {
        $member = user()->model();

        $active = MemberPlatformActive::where('member_id', $member->id)
            ->where('platform_id', $this->platform->id)
            ->first();

        // 如果使用者沒有登錄過這個資料，生成！
        if (! $active) {
            $active              = new MemberPlatformActive();
            $active->member_id   = $member->id;
            $active->platform_id = $this->platform->id;
            $active->enabled     = 1;
        }

        // 如果使用者沒有帳號，生成帳號密碼
        if (! $active->player_id) {
            $platform_acc  = preg_replace('/[^A-Za-z0-9 ]/', '', crypt(Uuid::uuid4(), time()));
            $platform_pass = preg_replace('/[^A-Za-z0-9 ]/', '', crypt(Uuid::uuid4(), time()));

            $active->player_id = substr($platform_acc, 0, 10);
            $active->password  = $platform_pass;

            $active->saveOrError();
        }

        return $active;
    }

    /**
     * 取得玩家 MemberPlatformActive 資料.
     *
     * @param int $member_id
     * @return MemberPlatformActive
     */
    protected function getPlatformActiveById(int $member_id)
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
     * 取得玩家 MemberPlatformActive 資料.
     *
     * @param string $playerId
     * @return MemberPlatformActive
     */
    protected function getPlatformActive(string $playerId)
    {
        $active = MemberPlatformActive::where('platform_id', $this->platform->id)
                ->where('player_id', $playerId)
                ->first();

        if (! $active) {
            throw new ErrorException('player not found : '.$playerId.','.$this->platform->id);
        }

        return $active;
    }

    /**
     *  檢查單號是否儲存.
     *
     * @param string $uid
     * @return bool
     */
    protected function checkProviderTransaction(string $uid)
    {
        // 檢查這張單有沒有處理過
        $this->logProvider = LogProviderTransaction::where('transaction_id', $uid)
            ->where('platform_id', $this->platform->id)
            ->first();

        // 有資料表示處理過了，應該要直接返還，不處理點數和報表
        if ($this->logProvider) {
            return true;
        }

        return false;
    }

    /**
     * 執行各項進入遊戲前的檢查.
     *
     * @param [type] $gameId
     * @param Member $member
     * @return void
     */
    protected function checkLaunchGame($gameId, $member)
    {
        // $member = user()->model();

        $active = $this->getPlatformActiveById($member->id);

        $game = $this->getGames($gameId, static::GAME_OPTION_FIRST);

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
        $numAgents = AgentPlatformConfig::whereIn('agent_id', array_slice($member->parentIds(), 1))->where('platform_id', $this->platform->id)->count();

        if ($numAgents !== 4) {
            throw new GameNotAllowedException(__('provider.parent-agents-error'));
        }

        // 建立 player_id
        if (! $active->player_id) {
            throw new ErrorException('不正確登入程序 (player id 不存在');
        }

        $active->loggedin_at = date('Y-m-d H:i:s');
        $active->save();
    }

    /**
     * 檢查錢是不是夠.
     *
     * @param int $mid
     * @param float $amount
     * @return void
     */
    protected function checkAmount(int $mid, float $amount)
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
    protected function modifyMemberWallet(int $member_id, float $amount, string $comment, string $type, float $before_point, float $after_point)
    {
        $memberWalletLog               = new LogMemberWallet();
        $memberWalletLog->member_id    = $member_id;
        $memberWalletLog->type         = $type;
        $memberWalletLog->type_id      = $this->platform->id;
        $memberWalletLog->change_money = $amount;
        $memberWalletLog->before_point = $before_point;
        $memberWalletLog->after_point  = $after_point;

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
    public function logTransaction(int $rid, string $tid, LogMemberWallet $logWallet)
    {
        // 檢查該序號是不是已經用過 且發放
        if (config('app.stg_special') === true) {
            // $cnt = LogProviderTransaction::where('transaction_id', $tid)->where('report_id', $rid)->count();

            // if($cnt > 0)
            // {
            //     return;
            // }
        }

        try {
            $logWallet->saveRecord();
        } catch (\Exception  $err) {
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

        return $logTransaction;
    }

    /**
     * 計算流水（任務、提款）.
     *
     * @param Member $member
     * @param Report $report
     * @param GamePlatform $platform
     * @return void
     */
    public function calBetAmount(Member $member, Report $report, GamePlatform $platform)
    {
        $bet_at = '';

        // 注意：只有當report是資料庫回來的時候。時間才會預設是carbon，所以是文字的話要轉一下
        if (is_string($report->bet_at)) {
            $bet_at = Carbon::parse($report->bet_at)->format('Y-m-d H:i:s');
        } else {
            $bet_at = $report->bet_at->format('Y-m-d H:i:s');
        }

        // 檢查，注單必須完成才能計算流水
        if ($report->status !== Report::STATUS_COMPLETED) {
            return;
        }

        // 活動錢包如果有算流水, 一般鎖定就不該算
        $activityWalletFlag = false;
        if (config('app.FUNCTION_MODE') == 'tb') {
            $act_wallet_service = new ActivityWalletService();
            $activityWalletFlag = $act_wallet_service->calWater($report);
        }

        // 計算提款流水
        if (config('app.FUNCTION_MODE') != 'stg' && $activityWalletFlag === false) {
            // 只要不是STG就要自己算流水
            $franchiseeWaterMultipleService = new FranchiseeWaterMultipleService();

            $franchiseeWaterMultipleService->calculation_member_bet_amount($member->id, $report);
        }

        // Artisan::call('cal:water', ['reportId' => $report->id]);

        // 計算流水，任務或提款限制
        // 計算任務流水
        switch (config('app.quest_version')) {
            case 'combine':
                $questService = new QuestCombineService();
                $questService->setMember($member);
                $questService->setReport($report);
                $questService->setPlatform($platform);
                $questService->countRewards();
                break;

            case 'original':
                $questService = new QuestService($member);

                if ($platform->special_type === 1) {
                    // 反波特例 用投注去當流水
                    $questService->countRewards($report->id, $report->game_id, $report->bet_amount, $bet_at);
                } else {
                    // 正常狀況下 special_type = 0 就是使用有效投注去算流水
                    $questService->countRewards($report->id, $report->game_id, $report->valid_amount, $bet_at);
                }
                break;

            default:
                throw new ErrorException(__('quest.unset-config'));
                break;
        }
    }

    /**
     * 同步注單並寫入log.
     *
     * @param SyncCallBackParameter $rows
     * @param SyncReportParameter $srp
     * @return void
     */
    protected function doSyncReports($rows, SyncReportParameter $srp)
    {
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

        // 剔除重複的id
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

        // 取得遊戲平台的特殊處理設定 不用with的原因是，萬一是新單就會錯囉！
        $game_platforms = GamePlatform::select('id', 'special_type')->get()->keyBy('id');

        $result                = new SyncResultFeedback();
        $result->total         = 0;
        $result->num_completes = 0;
        $result->num_fails     = 0;
        $fails                 = [];

        foreach ($rows as $row) {
            $result->total += 1;

            $player = $players[$row->username];

            $game = $games[$row->gameCode] ?? null;
            if (! $game) {
                throw new ErrorException("game not found. code={$row->gameCode}");
            }

            $report   = $reports[$row->mid] ?? null;
            $rankConf = null;
            $fconf    = null;
            $parents  = null;

            if (! $report) {
                $member = $player->member;

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
                    $parents = [];

                    for ($lv = 1; $lv <= 5; $lv++) {
                        $parents[] = $agconfigs[$player->member->{"alv{$lv}"}];
                    }

                    $memParents[$member->id] = $parents;
                }
            }

            [$report, $detail] = $this->generateReport($player, $game, $report, $row, $rankConf, $fconf, $parents);

            try {
                DB::beginTransaction();

                $report->updateTotal($detail);

                // $report->saveOrError();

                $detail->id = $report->id;
                $detail->saveOrError();

                // 計算流水
                $this->calBetAmount($player->member, $report, $game_platforms[$report->platform_id]);

                DB::commit();
                $result->num_completes += 1;
                $result->successMids[] = $report->mid;
            } catch (Exception | \Throwable | \ErrorException $err) {
                DB::rollBack();
                event(new ThrowException($err));
                $result->num_fails += 1;
                $fails[] = ['message' => $err->getMessage(), 'row' => $row];
            }
        }

        $result->status = $result->num_fails ? LogSyncReport::STATUS_FAILED : LogSyncReport::STATUS_COMPLETED;
        $result->fails  = $fails;

        // 碰到特殊格式的日期時要排除
        $start_at = explode('+', $srp->startAt);
        $endAt    = explode('+', $srp->endAt);

        // 寫入log
        $log                = new LogSyncReport();
        $log->platform_id   = $this->platform->id;
        $log->total         = $result->total;
        $log->num_completes = $result->num_completes;
        $log->num_fails     = $result->num_fails;
        $log->stime         = $start_at[0];
        $log->etime         = $endAt[0];
        $log->fails         = $result->fails;
        $log->message       = [];
        $log->status        = $result->status;
        $log->saveOrError();

        return $result;
    }

    /**
     * 建立優惠report.
     *
     * @param Report $report
     * @param TransferParameter $parameter
     * @return ReportPrize
     */
    public function generatePrize(Report $report, TransferParameter $parameter)
    {
        $prize            = new ReportPrize();
        $prize->report_id = $report->id;
        $prize->pid       = $parameter->syncCallBackParam->mid;
        $prize->amount    = $parameter->amount;
        $prize->prize_at  = $parameter->syncCallBackParam->reportAt;
        $prize->content   = $parameter->syncCallBackParam->content;

        return $prize;
    }

    /**
     * 產生單一 report，已存在就幫忙填資料.
     *
     * @param MemberPlatformActive $player 玩家資料
     * @param Game $game 部分遊戲商下注的時後不會帶 game code, 只能 sync report 再更新
     * @param Report $report
     * @param SyncCallBackParameter $row
     * @param ClubRankConfig $rankConf 會員所屬俱樂部設定
     * @param FranchiseePlatformConfig $fconf 會員所屬加盟商設定
     * @param AgentPlatformConfig[] $parents 會員上層代理設定
     * @return void
     */
    protected function generateReport($player, $game, $orig_report, $row, $rankConf = null, $fconf = null, $parents = null)
    {
        $member = $player->member;
        $report = $orig_report;
        $detail = $report ? $report->detail : null;

        if (! $report) {
            [$report, $detail] = $this->createReport($member, $game, $fconf, $rankConf, $parents);
        }

        // 如果沒給資料，就不幫忙填進去了
        if ($row != null) {
            $report->mid                   = $row->mid;
            $report->bet_at                = $row->betAt;
            $report->report_at             = $row->reportAt;
            $report->win_amount            = $row->winAmount;
            $report->prize                 = $row->prize;
            $report->tip                   = $row->tip;
            $report->status                = $row->status;
            $report->provider_water_amount = $row->waterAmount ?: 0;

            // $report->bet_amount            += $row->betAmount;
            // $report->valid_amount          += $row->validAmount;
            $report->bet_amount            = $row->betAmount ?: 0;
            $report->valid_amount          = $row->validAmount ?: 0;
            $report->settle_at             = $row->settleAt;

            $detail->table     = $row->table ?: '';
            $detail->round     = $row->round;

            if (config('app.stg_special') === true) {
                if ($row->content) {
                    if (\Config::get('app.STG_MULTI') == true) {
                        $detail->content = $row->content;
                    } else {
                        if ($detail->content) {
                            $content           = json_decode($detail->content, true);
                            $ary               = explode('-', $row->content);
                            $content['hr']     = $ary[0];
                            $content['cr']     = $ary[1];
                            $content['gameAt'] = $row->gameAt;
                            $detail->content   = json_encode($content);
                        } else {
                            $detail->content = $row->content;
                        }
                    }
                } else {
                    $detail->content = '';
                }
            } else {
                $detail->content   = $row->content ?: '';
            }

            $detail->report_at = ($detail->report_at) ? $detail->report_at : (($row->reportAt) ? $row->reportAt : '');
            $detail->ip        = $row->ip;
        }

        return [$report, $detail];
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
        $agconfigs = AgentPlatformConfig::select('agent_id', 'percent', 'water_percent', 'bonus_percent')
            ->whereIn('agent_id', $member->parentIds())
            ->where('platform_id', $platform->id)
            ->get();

        /**
         * @var AgentPlatformConfig[] $agconfigs
         */
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
        $detail->allocate_agent_water_percent = $fconf->water_percent;

        // 代理紅利提額度
        $detail->allocate_agent_bonus_percent = $fconf->bonus_percent;

        // 會員紅利提撥比
        $detail->allocate_member_bonus_percent = $fconf->allocate_member_bonus_percent;

        // 會員退水提撥比 (部分遊戲商可能在下注的時後不會給 game code, 所以會員退水比可能會是 0)
        $detail->allocate_member_water_percent = $crconf->water_percent ?? 0;

        $report->company_percent = 100 - $fconf->percent;

        // 公司不佔任何退水、紅利比例
        $detail->company_water_percent = 0;
        $detail->company_bonus_percent = 0;

        /**
         * 各佔成的「實佔」計算 = (上層下放值 - 本層下放值)
         * 例：
         *  加盟商下放 90 => 實際佔成 10% (100 - 90)
         *  lv 1 下放 75 =>  實際佔成 15% (90 - 75).
         */
        $prevPercent      = $fconf->percent;
        $prevWaterPercent = $fconf->water_percent;
        $prevBonusPercent = $fconf->bonus_percent;

        for ($lv = 1; $lv <= 4; $lv++) {
            $aid        = $member->{"alv{$lv}"};
            $nextLv     = $lv + 1;
            $nextConfig = $agconfigs[$member->{"alv{$nextLv}"}] ?? null;
            if (! $nextConfig) {
                throw new ErrorException("agent_platform_config not found. pid={$platform->id} aid = ".$member->{"alv{$nextLv}"});
            }
            $report->{"alv{$lv}"}               = $aid;
            $report->{"alv{$lv}_percent"}       = $prevPercent - $nextConfig->percent;
            $detail->{"alv{$lv}_water_percent"} = $prevWaterPercent - $nextConfig->water_percent;
            $detail->{"alv{$lv}_bonus_percent"} = $prevBonusPercent - $nextConfig->bonus_percent;

            $prevPercent      = $nextConfig->percent;
            $prevWaterPercent = $nextConfig->water_percent;
            $prevBonusPercent = $nextConfig->bonus_percent;
        }

        // 最後一層，以設定的值為主
        $config = $agconfigs[$member->{'alv5'}] ?? null;
        if (! $config) {
            throw new ErrorException("agent_platform_config not found. pid={$platform->id} aid = ".$member->{"alv{$nextLv}"});
        }

        // 設定最後一層的佔成
        $report->alv5               = $config->agent_id;
        $report->alv5_percent       = $config->percent;
        $detail->alv5_water_percent = $config->water_percent;
        $detail->alv5_bonus_percent = $config->bonus_percent;

        // 設定上 3 層會員的紅利分配比, 若不存在, 則為 0
        $report->mlv1               = $member->mlv1;
        $report->mlv2               = $member->mlv2;
        $report->mlv3               = $member->mlv3;
        $detail->mlv1_bonus_percent = $report->mlv1 ? $fconf->mlv1_bonus_percent : 0;
        $detail->mlv2_bonus_percent = $report->mlv2 ? $fconf->mlv2_bonus_percent : 0;
        $detail->mlv3_bonus_percent = $report->mlv3 ? $fconf->mlv3_bonus_percent : 0;

        return [$report, $detail];
    }

    /**
     * 取得玩家錢包.
     *
     * @param int $mid member.id
     * @return MemberWallet
     */
    protected function getWallet($mid)
    {
        /** @var MemberWallet $wallet */
        $wallet = MemberWallet::find($mid);
        if (! $wallet) {
            throw new ErrorException('wallet not found');
        }

        return $wallet;
    }

    protected function getGames($codes, int $game_option)
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
            $game = $games->first();

            // 找不到遊戲用預設值
            $gameCode = $this->key.'00';

            if (! $game) {
                $game = $this->getGames($gameCode, static::GAME_OPTION_FIRST);
            }

            return $game;
        }

        throw new ErrorException('game option not found');
    }

    /**
     * 檢查token是否正確.
     *
     * @param string $token
     * @return bool
     */
    protected function checkAuthorizeTokenValid($token)
    {
        // 查token是否存在
        if (! AccessService::isValidAccessToken($token)) {
            return false;
        }

        return true;
    }

    /**
     * 檢查token是否過期
     *
     * @param string $token
     * @return bool
     */
    protected function checkAuthorizeTokenExpire($token)
    {
        // 查token是否過期
        $active = AccessService::veirfyAccessToken($token);
        if (! $active) {
            return false;
        }

        return $active;
    }

    /**
     * 建立token並寫入redis.
     *
     * @return string
     */
    protected function setAuthorizeToken()
    {
        $active = MemberPlatformActive::where('player_id', $LGP->member->playerId)
                ->where('platform_id', $this->platform->id)
                ->first();

        if (! $active) {
            throw new ErrorException("player not found > {$playerId}");
        }

        $access = new AccessService($active);

        return $access->generateAccessToken();
    }

    /**
     * 檢查使用者可不可以投注.
     *
     * @param Member $member
     * @return void
     */
    protected function checkMemberBetPermission($member)
    {
        if (! $member) {
            throw new ErrorException(__('member.not-found'));
        }

        // 檢查使用者是否啟用
        if ($member->isDisabled()) {
            throw new ForbiddenException(__('member.disabled'));
        }

        // 查使用者有沒有被鎖
        if ($member->isLocked()) {
            throw new ForbiddenException(__('member.locked'));
        }
    }

    protected function doWalletTransaction($uid, $report, $detail, $log, $walletType, $calBetAmount = false)
    {
        return DB::transaction(function () use ($uid, $report, $detail, $log, $walletType, $calBetAmount) {
            $amount = $report->updateTotal($detail);

            if (! $report->save()) {
                throw new SaveFailedException('report');
            }

            $detail->id = $report->id;
            if (! $detail->save()) {
                throw new SaveFailedException('report-detail');
            }

            if (! $amount) {
                return;
            }

            // 等 report 建完後才扣款
            if (LogMemberWallet::isVerify($amount)) {
                $log->member_id    = $report->member_id;
                $log->type         = $walletType;
                $log->type_id      = $report->id;
                $log->change_money = $amount;

                // 計算流水
                if ($calBetAmount === true) {
                    // 取得遊戲平台的特殊處理設定 不用with的原因是，萬一是新單就會錯囉！
                    $game_platforms = GamePlatform::select('id', 'special_type')->where('id', $report->platform_id)->get()->keyBy('id');

                    $this->calBetAmount($log->member, $report, $game_platforms[$report->platform_id]);
                }
                // 更新交易記錄
                return $this->logTransaction($report->id, $uid, $log);
            } else {
                throw new ErrorException('error amount');
            }
        });
    }

    public function query($key = null, $default = null)
    {
        return (function_exists('request') && get_class(request()) === 'Illuminate\Http\Request')
            ? request()->query($key, $default)
            : $this->arrayGet($_GET, $key, $default);
    }
}
