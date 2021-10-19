<?php

namespace GameProvider\Services;

use App\Exceptions\ErrorException;
use App\Models\GamePlatform;
use App\Models\LogMemberWallet;
use App\Models\LogProviderTransaction;
use App\Models\Member;
use App\Models\MemberPlatformActive;
use App\Models\MemberWallet;
use App\Models\Report;
use App\Services\Provider\AccessService;
use DB;
use Exception;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\BetNotFoundException;
use GameProvider\Exceptions\ForbiddenException;
use GameProvider\Exceptions\FundsExceedException;
use GameProvider\Exceptions\SaveFailedException;
use GameProvider\Exceptions\StuckMoneyException;
use GameProvider\Exceptions\TransferException;
use GameProvider\Operator\Feedback\LaunchGameFeedback;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;
use GameProvider\Operator\Single\Api\BNG;
use GameProvider\Operator\Single\BaseSingleWalletInterface;

class BNGService extends BaseWalletService
{
    /**
     * 轉接環.
     *
     * @var BaseSingleWalletInterface
     */
    protected $api;

    // function __construct()
    // {
    //     $this->platform = GamePlatform::where('key', 'BNG')->first();
    //     $this->key      = 'BNG';
    //     $this->api      = $this->platform->getPlatformModule();
    // }

    public function __construct(array $config)
    {
        $this->key      = 'bng';
        $this->platform = GamePlatform::where('key', $this->key)->first();
        $this->api      = new BNG($config);
    }

    public function getAction()
    {
        return $this->input('name');
    }

    /**
     * 執行動作（路由）.
     *
     * @return void
     */
    public function action()
    {
        $action = $this->input('name');
        switch ($action) {
            case 'launchGame':
                return $this->launchGame($parameter);
                break;
            // case 'syncReport':
            //     return $this->syncReport($parameter);
            //     break;
            case 'login':
                return $this->login();
                break;
            case 'getbalance':
                return $this->getBalance();
                break;
            case 'transaction':
                return $this->transaction();
                break;
            case 'rollback':
                return $this->rollback();
                break;
            default:
                throw new Exception('BNG do not have '.$action.' action !');
        }
    }

    public function login()
    {
        $uid         = $this->input('uid');
        $session     = $this->input('session');
        $accessToken = $this->input('token');
        $gameId      = $this->input('game_id');

        // 查token是否存在
        if (! $this->checkAuthorizeTokenValid($accessToken)) {
            return $this->generateErrorMsg($uid, 'INVALID_TOKEN');
        }

        // 查token是否過期
        $active = $this->checkAuthorizeTokenExpire($accessToken);
        if ($active === false) {
            return $this->generateErrorMsg($uid, 'EXPIRED_TOKEN');
        }

        // 檢查所有玩家項目
        $member = Member::findOrError($active->member_id);

        $this->checkLaunchGame($gameId, $member);

        $wallet = MemberWallet::findOrError($member->id);

        return response(
            [
                'uid'    => $uid,
                'player' => [
                    'id'       => $active->player_id,
                    'currency' => $this->platform->currency,
                    'mode'     => 'REAL',
                    'is_test'  => false,
                ],
                'balance' => [
                    'value'   => strval($wallet->money),
                    'version' => $wallet->version ?: 0,
                ],
                'tag' => '',
            ]
        );
    }

    public function getBalance($need_encode = true)
    {
        $uid       = $this->input('uid');
        $playerId  = $this->input('args.player.id');

        $active = $this->getPlatformActive($playerId);

        return $this->generateTransactionMsg($active, $uid);
    }

    public function transaction()
    {
        $uid            = $this->input('uid');
        $mid            = $this->input('args.round_id');
        $betAmount      = $this->input('args.bet');
        $winAmount      = $this->input('args.win');
        $playerId       = $this->input('args.player.id');
        $gameId         = $this->input('game_id');
        $round_finished = $this->input('args.round_finished');

        $parameter               = new SyncCallBackParameter();
        $parameter->mid          = $mid;
        $parameter->uid          = $uid;
        $parameter->username     = $playerId;
        $parameter->betAmount    = floatval($betAmount);
        $parameter->validAmount  = floatval($betAmount);
        $parameter->winAmount    = floatval($winAmount);
        $parameter->gameCode     = $gameId;
        $parameter->reportAt     = localeDatetime($this->input('c_at'));
        $parameter->betAt        = localeDatetime($this->input('c_at'));

        $platform = $this->platform;
        $active   = $this->getPlatformActive($playerId);

        $report = Report::where('platform_id', $platform->id)
                ->where('mid', $mid)
                ->first();

        $game = $this->getGames($gameId, static::GAME_OPTION_FIRST);

        // 有下注額，當作是投注
        if ($betAmount !== null) {
            // 查錢夠不夠
            $wallet = $this->getWallet($active->member_id);
            if ($wallet->money < $parameter->betAmount) {
                throw new FundsExceedException(__('provider.found-exceed'));
            }

            $member = Member::findOrError($active->member_id);

            $this->checkMemberBetPermission($member);

            $detail = $report ? $report->detail : null;

            $parameter->status    = Report::STATUS_BETTING;

            // 如果有中獎金額，結束
            if ($parameter->winAmount !== null) {
                $parameter->status    = Report::STATUS_COMPLETED;
            }
            // $parameter->winAmount = 0; // 不動輸贏

            [$report, $detail] = $this->generateReport($active, $game, $report, $parameter);

            $log = new LogMemberWallet();

            $this->doWalletTransaction($uid, $report, $detail, $log, LogMemberWallet::TYPE_BET, true);

            return $this->generateTransactionMsg($active, $uid);
        }

        // 有輸贏，可能為派彩或彩金
        if ($winAmount !== null) {
            $detail = $report ? $report->detail : null;

            if (! $report || ! $detail) {
                throw new ErrorException('report or detail error');
            }

            // 如果狀態是投注中
            if ($this->isReportBetting($mid)) {
                $winAmount = $winAmount !== null
                    ? floatval($winAmount)
                    : ($report->bet_amount + floatval($winAmount));

                $report->win_amount = $winAmount;
                $report->settle_at  = localeDatetime($this->input('c_at'));

                $report->status = Report::STATUS_SETTLE;

                $log = new LogMemberWallet();

                $this->doWalletTransaction($uid, $report, $detail, $log, LogMemberWallet::TYPE_SETTLE, false);

                return $this->generateTransactionMsg($active, $uid);
            }

            // 非同步中，應該要加進去彩金
            $prize            = new ReportPrize();
            $prize->report_id = $report->id;
            $prize->pid       = $uid;
            $prize->amount    = floatval($winAmount);
            $prize->prize_at  = localeDatetime($this->input('c_at'));
            $prize->content   = '';

            if (! $prize->save()) {
                throw new SaveFailedException(__('provider.prize-save-failed'));
            }

            $report->prize += $data->prize;

            // 更新各層額度比例
            $changeValue = $report->updateTotal();

            if ($changeValue) {
                $log->member_id    =  $report->member_id;
                $log->type         = LogMemberWallet::TYPE_PRIZE;
                $log->type_id      =  $report->id;
                $log->change_money =  $changeValue;
                $this->logTransaction($report->id, $data->uid, $log);
            }
        }

        // 拿餘額(沒投注沒開獎，直接返回餘額)
        return $this->generateTransactionMsg($active, $uid);
    }

    public function rollback()
    {
        $uid           = $this->input('uid');
        $mid           = $this->input('args.round_id');
        $transactionId = $this->input('args.transaction_uid');
        $playerId      = $this->input('args.player.id');

        $active = $this->getPlatformActive($playerId);
        $rlog   = new LogMemberWallet();

        // 如果沒有處理過這個
        if ($this->checkProviderTransaction($uid) !== true) {
            // 因為本來就沒有這筆支出，所以沒什麼好處理的，直接返還對方需要的
            return $this->generateTransactionMsg($active, $uid);
        }

        $logWallet = $this->logProvider->log;

        // rollback 下注資料
        $rlog->member_id = $active->member_id;
        $rlog->type      = LogMemberWallet::TYPE_ROLLBACK;
        $rlog->type_id   = $logWallet->id;

        $report = Report::findOrError($logWallet->type_id);

        if ($logWallet->type === LogMemberWallet::TYPE_BET) {
            // 如果那次操作是投注，把錢加回來
            $report->bet_amount += $logWallet->change_money;
            $rlog->change_money = $report->total;

            // 若只有一筆子單, 把主單狀態改為 rollback
            if ($report->bet_amount === 0) {
                $report->status = Report::STATUS_ROLLBACK;
            }
        } elseif ($logWallet->type === LogMemberWallet::TYPE_SETTLE) {

            // 如果上次動作是派彩，把金額扣掉
            $report             = Report::findOrError($logWallet->type_id);
            $rlog->change_money = -$report->total;
            $report->status     = Report::STATUS_BETTING;
        }

        return DB::transaction(function () use ($report, $rlog, $uid, $active) {
            $rlog->change_money = $report->updateTotal();

            $this->logTransaction($this->logProvider->report_id, $uid, $rlog);

            return $this->generateTransactionMsg($active, $uid);
        });
    }

    /**
     * 進入遊戲.
     *
     * @param LaunchGameParameter $LGP
     * @return LaunchGameFeedback
     */
    public function launchGame(LaunchGameParameter $LGP)
    {
        $member = Member::findOrError($LGP->member->member_id);

        $this->checkLaunchGame($LGP->gameId, $member);

        // $LGP->token = $this->setAuthorizeToken();

        return $this->api->launchGame($LGP);
    }

    private function generateTransactionMsg($active, $uid)
    {
        // 拿餘額
        $wallet = MemberWallet::findOrError($active->member_id);

        return response([
            'uid'     => $uid,
            'balance' => [
                'value'   => strval($wallet->money),
                'version' => intval($wallet->version),
            ],
        ]);
    }

    private function isReportBetting($mid)
    {
        $query = Report::where('platform_id', $this->platform->id)
            ->where('mid', $mid);
        $status = $query->value('status');

        return $status === Report::STATUS_BETTING;
    }

    private function generateErrorMsg($uid, $message)
    {
        return response([
            'uid'   => $uid,
            'error' => ['code' => $message],
        ]);
    }
}
