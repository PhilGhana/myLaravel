<?php

namespace GameProvider\Services;

use App\Exceptions\ErrorException;
use App\Models\GamePlatform;
use App\Models\LogMemberWallet;
use App\Models\LogProviderTransaction;
use App\Models\MemberPlatformActive;
use App\Models\MemberWallet;
use App\Models\Report;
use App\Services\Provider\AccessService;
use Exception;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\FundsExceedException;
use GameProvider\Exceptions\StuckMoneyException;
use GameProvider\Exceptions\TransferException;
use GameProvider\Operator\Feedback\LaunchGameFeedback;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;
use GameProvider\Operator\Single\BaseSingleWalletInterface;

class SingleWalletService extends BaseWalletService
{
    /**
     * 轉接環.
     *
     * @var BaseSingleWalletInterface
     */
    protected $api;

    /**
     * 平台的代碼
     *
     * @var string
     */
    protected $key;

    public function __construct($key)
    {
        $this->platform = GamePlatform::where('key', $key)->first();
        $this->api      = $this->platform->getPlatformModule();
        $this->key      = $key;
    }

    /**
     * 執行動作，因為變動可能大，交給轉接環處理.
     *
     * @param string $action
     * @param object $parameter
     * @return void
     */
    public function action($action, $parameter)
    {
        $this->api->action($this, $action, $parameter);
    }

    /**
     * 以下為別人來呼叫我們.
     */

    /**
     * 廠商來驗證token時用的.
     *
     * @return string
     */
    public function authorize()
    {
        $authParam = $this->api->getAuthorizeParams();

        // 查token
        $isValid = $this->checkToken($authParam->token);

        // 查有沒有使用者，順便取餘額
        $active = MemberPlatformActive::where('platform_id', $this->platform->id)
                ->where('player_id', $authParam->playerId)
                ->first();

        if (! $active) {
            return $this->api->responseAuthorize(0, $authParam, $isValid, false);
        }

        $wallet = MemberWallet::findOrError($active->member_id);

        return $this->api->responseAuthorize($wallet->money, $authParam, $isValid, true);
    }

    /**
     * 取餘額.
     *
     * @return string
     */
    public function getBalance()
    {
        $balanceParam = $this->api->getBalanceParams();

        // 查有沒有使用者
        $active = MemberPlatformActive::where('platform_id', $this->platform->id)
                ->where('player_id', $balanceParam->playerId)
                ->first();

        if (! $active) {
            return $this->api->responseBalance(0, $balanceParam, false);
        }

        // 取餘額
        $wallet = MemberWallet::findOrError($active->member_id);

        return $this->api->responseBalance($wallet->money, $balanceParam);
    }

    public function transfer(TransferParameter $parameter)
    {
        if ($parameter === TransferParameter::TYPE_PAY_IN) {
            return $this->payIn($parameter);
        }

        if ($parameter === TransferParameter::TYPE_PAY_OUT) {
            return $this->payOut($parameter);
        }

        if ($parameter === TransferParameter::TYPE_PAY_PRIZE) {
            return $this->payPrize($parameter);
        }

        if ($parameter === TransferParameter::TYPE_PAY_IN_CANCEL) {
            return $this->rollback($parameter);
        }
    }

    /**
     * 派彩或對方出點數給平台.
     *
     * @param TransferParameter $parameter
     * @return void
     */
    public function payIn(TransferParameter $parameter)
    {
        // 這邊不動注單，就算有資訊也依樣，客戶不會在乎點數變多了，直接把這裡交給同步去處理就好
        // 因為各種情況太多了，獨立處理有太多例外，所以只處理點數
        $active = $this->getPlatformActive($parameter->member->playerId);

        // 檢查這張單有沒有處理過
        $checkResult = $this->checkProviderTransaction($parameter);

        if ($checkResult !== false) {
            return $checkResult;
        }

        // 特例，當有referenceId時，要找是不是存在，不然應該要報錯給遊戲商
        // 因為不存在轉入，相對就不應該有派彩
        if ($parameter->referenceId === null) {
            return $this->api->responsePayInError();
        }

        $logProvider = LogProviderTransaction::where('transaction_id', $parameter->referenceId)
            ->where('platform_id', $this->platform->id)
            ->first();

        if (! $logProvider) {
            return $this->api->responsePayInError();
        }

        $amount = $parameter->amount;
        // 避免金額正負錯誤
        if ($amount > 0) {
            $amount = -floatval($amount);
        }

        $log               = new LogMemberWallet();
        $log->member_id    = $active->member_id;
        $log->type         = $parameter->logType;
        $log->type_id      = $logProvider->report_id;
        $log->change_money = $amount;

        // 如果有注單就順便綁起來，但是不動內容，一切交給同步比對
        $report = Report::where('platform_id', $platform->id)
            ->where('mid', $parameter->syncCallBackParam->mid)
            ->first();

        $rid = 0;

        if ($report) {
            $rid = $report->id;
        }

        return DB::transaction(function () use ($parameter, $log, $rid) {
            $logProvider = $this->logTransaction($rid, $parameter->syncCallBackParam->uid, $log);

            return $this->api->responsePayIn($logProvider->id, $logProvider->log->after_money, $parameter);
        });
    }

    /**
     * 回滾
     * 把點數轉進來，用在取消注單時、移除派彩、取消彩金、取消紅包.
     *
     * @param TransferParameter $parameter
     * @return void
     */
    public function rollback(TransferParameter $parameter)
    {
        $active = $this->getPlatformActive($parameter->member->playerId);

        // 沒有應對的流水單，不可以取消
        if ($parameter->referenceId === null) {
            return $this->api->responseRollbackError();
        }

        // 檢查這張單有沒有處理過
        $checkResult = $this->checkProviderTransaction($parameter);

        if ($checkResult !== false) {
            return $checkResult;
        }

        return DB::transaction(function () {

            // 把當時的金額取回來看看
            $logTransaction = LogProviderTransaction::where('platform_id', $this->platform->id)
            ->with('log')
            ->with('report')
            ->where('transaction_id', $parameter->referenceId)
            ->first();

            // 沒有轉帳記錄不應該取消注單
            if (! $logTransaction) {
                return $this->api->responseRollbackError();
            }

            $report    = $logTransaction->report;
            $logWallet = $logTransaction->log;

            $rlog            = new LogMemberWallet();
            $rlog->member_id = $active->member_id;
            $rlog->type      = LogMemberWallet::TYPE_ROLLBACK;
            $rlog->type_id   = $logWallet->id;

            $now = date('Y-m-d H:i:s');

            // rollback的情況很多，一個一個來
            // 如果是投注狀態
            if ($logWallet->type === LogMemberWallet::TYPE_BET) {
                // 這個狀況rollback就是退錢
                $report->bet_amount += $logWallet->change_money;

                // 若只有一筆子單, 把主單狀態改為 rollback
                if ($report->bet_amount === 0) {
                    $report->status = Report::STATUS_ROLLBACK;
                }

                $rlog->change_money = $report->updateTotal();
            }
            // 如果是紅包、彩金
            elseif ($logWallet->type === LogMemberWallet::TYPE_PRIZE) {
                $prize = ReportPrize::where('report_id', $report->id)->first();
                $prize->cancel_at = $now;

                if (! $prize->save()) {
                    throw new SaveFailedException('prize');
                }

                $rlog->change_money = $report->updateTotal();
            }
            // 如果是派彩
            elseif ($logWallet->type === LogMemberWallet::TYPE_SETTLE) {
                $rlog->change_money = -$report->total;
                $report->status = Report::STATUS_BETTING;
                $rlog->change_money = $report->updateTotal();
            }

            $logProvider = $this->logTransaction($logTransaction->report_id, $parameter->syncCallBackParam->uid, $rlog);

            return $this->api->responseRollback($logProvider->id, $logProvider->log->after_money, $parameter);
        });
    }

    /**
     * 把點數轉出去，一般用在投注時
     *
     * @param TransferParameter $parameter
     * @return void
     */
    public function payOut(TransferParameter $parameter)
    {
        $platform = $this->platform;
        $active   = $this->getPlatformActive($parameter->member->playerId);

        // 查錢夠不夠
        $wallet = $this->getWallet($active->member_id);
        if ($wallet->money < $parameter->amount) {
            throw new FundsExceedException(__('provider.found-exceed'));
        }

        // 檢查是否有使用者
        $member = Member::findOrError($active->member_id);
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

        // 檢查這張單有沒有處理過
        $checkResult = $this->checkProviderTransaction($parameter);

        if ($checkResult !== false) {
            return $checkResult;
        }

        // 沒資料不管怎麼樣都要生出來一張單，避免同步前對不上
        // 拿注單
        $report = Report::where('platform_id', $platform->id)
            ->forceIndex()
            ->where('mid', $parameter->syncCallBackParam->mid)
            ->first();

        $detail = $report ? $report->detail : null;

        // 先給一個虛的遊戲名稱
        $gameCode = $this->key.'00';

        // 如果有給遊戲資訊
        if ($parameter->syncCallBackParam->gameCode !== null) {
            $gameCode = $parameter->syncCallBackParam->gameCode;
        }

        $game = $this->getGames($gameCode, static::GAME_OPTION_FIRST);

        if (! $game) {
            // 如果沒有這個遊戲，都往虛的去
            $gameCode = $this->key.'00';

            $game = $this->getGames($gameCode, static::GAME_OPTION_FIRST);
            // throw new ErrorException('not found game');
        }

        [$report, $detail] = $this->generateReport($active, $game, $report, $parameter->syncCallBackParam);

        $log = new LogMemberWallet();

        return DB::transaction(function () use ($parameter, $report, $detail, $log) {
            $amount = $report->updateTotal($detail);

            if (! $report->save()) {
                throw new SaveFailedException('report');
            }

            $detail->id = $report->id;
            if (! $detail->save()) {
                throw new SaveFailedException('report-detail');
            }

            // 等 report 建完後才扣款
            if (LogMemberWallet::isVerify($amount)) {
                $log->member_id    = $report->member_id;
                $log->type         = $parameter->logType;
                $log->type_id      = $report->id;
                $log->change_money = $amount;
                // 更新交易記錄
                $logProvider = $this->logTransaction($report->id, $parameter->syncCallBackParam->uid, $log);

                return $this->api->responsePayOut($logProvider->id, $logProvider->log->after_money, $parameter);
            } else {
                throw new ErrorException('error amount');
            }
        });
    }

    public function payPrize(TransferParameter $parameter)
    {
        // 先查mid是不是存在。存在代表為彩金（必須要有玩才會獲得的）、不存在代表紅包類
        $active   = $this->getPlatformActive($parameter->member->playerId);

        // 檢查這張單有沒有處理過
        $checkResult = $this->checkProviderTransaction($parameter);

        if ($checkResult !== false) {
            return $checkResult;
        }

        $report = Report::where('platform_id', $this->platform->id)
            ->where('mid', $parameter->syncCallBackParam->mid)
            ->first();

        $detail = $report ? $report->detail : null;

        $log = new LogMemberWallet();

        if (! $report) {
            // 紅包類，直接加開注單
            // 先給一個虛的遊戲名稱
            $gameCode = $this->key.'00';

            // 如果有給遊戲資訊
            if ($parameter->syncCallBackParam->gameCode !== null) {
                $gameCode = $parameter->syncCallBackParam->gameCode;
            }

            $game = $this->getGames($gameCode, static::GAME_OPTION_FIRST);

            if (! $game) {
                throw new ErrorException('not found game');
            }

            [$report, $detail] = $this->generateReport($active, $game, $report, $parameter->syncCallBackParam);
        }

        return DB::transaction(function () use ($parameter, $report, $log) {

            // 更新各層額度比例
            $changeValue = $report->updateTotal();

            // 建立彩金資料
            $prize            = new ReportPrize();
            $prize->report_id = $report->id;
            $prize->pid       = $parameter->syncCallBackParam->mid;
            $prize->amount    = $parameter->amount;
            $prize->prize_at  = $parameter->syncCallBackParam->reportAt;
            $prize->content   = $parameter->syncCallBackParam->content;
            $prize->save();

            if ($changeValue) {
                $log->member_id    = $report->member_id;
                $log->type         = $parameter->logType;
                $log->type_id      = $report->id;
                $log->change_money = $changeValue;

                $logProvider = $this->logTransaction($report->id, $parameter->syncCallBackParam->uid, $log);

                return $this->api->responsePayPrize($logProvider->id, $logProvider->log->after_money, $parameter);
            }
        });
    }

    /**
     * 以下為我們去呼叫別人的部分.
     */

    /**
     * 進入遊戲.
     *
     * @param LaunchGameParameter $LGP
     * @return LaunchGameFeedback
     */
    public function launchGame(LaunchGameParameter $LGP)
    {
        $this->checkLaunchGame($LGP->gameId);

        $active = MemberPlatformActive::where('player_id', $LGP->member->playerId)
                ->where('platform_id', $this->platform->id)
                ->first();

        if (! $active) {
            throw new ErrorException("player not found > {$playerId}");
        }

        if ($LGP->needToken) {
            $access     = new AccessService($active);
            $LGP->token = $access->generateAccessToken();
        }

        return $this->api->launchGame($LGP);
    }

    /**
     * 同步作業.
     *
     * @param SyncReportParameter $srp
     * @return void
     */
    public function syncReport(SyncReportParameter $srp)
    {
        return $this->api->syncReport($srp, function ($rows) use ($srp) {
            return $this->doSyncReports($rows, $srp);
        });
    }

    /**
     * 檢查遊戲方給的token是否正確.
     *
     * @param string $token
     * @return bool
     */
    private function checkToken($token)
    {
        // 查token是否存在
        if (! AccessService::isValidAccessToken($token)) {
            return false;
        }

        // 查token是否過期
        $active = AccessService::veirfyAccessToken($token);
        if (! $active) {
            return false;
        }

        return true;
    }

    /**
     *  檢查單號是否儲存.
     *
     * @param TransferParameter $parameter
     * @return bool string
     */
    private function checkProviderTransaction(TransferParameter $parameter)
    {
        // 檢查這張單有沒有處理過
        $logProvider = LogProviderTransaction::where('transaction_id', $parameter->syncCallBackParam->uid)
            ->where('platform_id', $this->platform->id)
            ->first();

        // 有資料表示處理過了，應該要直接返還，不處理點數和報表
        if ($logProvider) {
            return $logProvider;
            // return $this->api->responsePayOut($logProvider->id, $logProvider->log->after_money, $parameter);
        }

        return false;
    }
}
