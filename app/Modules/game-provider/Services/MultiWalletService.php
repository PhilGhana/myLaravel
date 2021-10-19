<?php

namespace GameProvider\Services;

use App\Exceptions\FailException;
use App\Models\GamePlatform;
use App\Models\LogMemberWallet;
use App\Models\LogProviderTransaction;
use App\Models\Member;
use App\Models\MemberPlatformActive;
use App\Models\StuckMoney;
use App\Services\Report\SyncReportService;
use DB;
use Exception;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\CurlException;
use GameProvider\Exceptions\FundsExceedException;
use GameProvider\Exceptions\SaveFailedException;
use GameProvider\Exceptions\StuckMoneyException;
use GameProvider\Exceptions\TransferException;
use GameProvider\Operator\Feedback\LaunchGameFeedback;
use GameProvider\Operator\Multi\BaseMultiWalletInterface;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;
use Illuminate\Support\Facades\Log;

class MultiWalletService extends BaseWalletService
{
    protected $baseMultiWalletApi = null;

    public function __construct(BaseMultiWalletInterface $baseMultiWalletApi, GamePlatform $platform)
    {
        $this->baseMultiWalletApi = $baseMultiWalletApi;

        $this->platform = $platform;
    }

    /**
     * 取遊戲列表.
     *
     * @return void
     */
    public function getGameList()
    {
        return $this->baseMultiWalletApi->getGameList();
    }

    /**
     * 建立使用者.
     *
     * @param MemberParameter $memberParam
     * @return void
     */
    public function createMember(MemberParameter $memberParam)
    {
        // $active = $this->createMemberToDB();

        $memberFeedback = $this->baseMultiWalletApi->createMember($memberParam);

        // 寫入資料
        if ($memberFeedback->extendParam !== null) {
            return $memberFeedback->extendParam;
            // $active = MemberPlatformActive::where('platform_id', $this->platform->id)
            //     ->where('member_id', $memberParam->member_id)
            //     ->first();

            // // 如果沒建立的話...正常來說，會跑到這邊肯定沒有，不過還是保險先查一下
            // if (! $active) {
            //     $active              = new MemberPlatformActive();
            //     $active->member_id   = $memberParam->member_id;
            //     $active->platform_id = $this->platform->id;
            //     $active->enabled     = 1;
            // }

            // $active->username = $memberFeedback->extendParam;
            // $active->saveOrError();
        }

        return true;
    }

    public function doCreateMember($member_id)
    {
        $member   = Member::findOrError($member_id);
        $platform = $this->platform;

        $active = MemberPlatformActive::where('member_id', $member->id)
        ->where('platform_id', $platform->id)
        ->first();

        if (! $active) {
            $active              = new MemberPlatformActive();
            $active->member_id   = $member->id;
            $active->platform_id = $platform->id;
            $active->enabled     = 1;
        }

        if (! $active->player_id) {

            // 這邊要特例修改 如果碰到有設定真實帳密的平台, 要丟真實的去
            $realAccountPlatforms = config('app.REAL_ACCOUNT_PLATFORMS');
            $tmpAry               = [];
            if ($realAccountPlatforms != '') {
                $tmpAry = explode(',', $realAccountPlatforms);
            }
            if (count($tmpAry) > 0 && in_array($platform->key, $tmpAry)) {
                $active->player_id = $member->account;
            } else {
                $active->generatePlayerId($platform->generatorMemberUsername($member->account), ! $platform->use_password);
                $active->player_id = substr($active->player_id, 0, 20);
            }
            $active->setPassword($member->account);

            $memberParams            = new MemberParameter();
            $memberParams->member_id = $member->id;
            $memberParams->playerId  = $active->player_id;
            $memberParams->password  = $active->getPassword();
            $memberParams->actype    = 1;

            $response = $this->createMember($memberParams, $member->id);

            if ($response !== true) {
                $active->username = $response;
            }

            $active->saveOrError();
        }

        return $active;
    }

    /**
     * 獲取使用者餘額.
     *
     * @param MemberParameter $member
     * @return float
     */
    public function getBalance(MemberParameter $member)
    {
        $balanceFeedback = $this->baseMultiWalletApi->getBalance($member);

        return $balanceFeedback->balance;
    }

    /**
     * 存款.
     *
     * @param TransferParameter $transferParam
     * @return void
     */
    public function deposit(TransferParameter $transferParam)
    {
        $member_id = $transferParam->member->member_id;
        // 這邊注意: 只給轉整數, 因為如果碰到遊戲不是用精度運算, 可能會造成卡錢...
        $amount    = intval($transferParam->amount);
        // 原本傳入的參數也要改
        $transferParam->amount = $amount;
        if ($amount <= 0) {
            return null;    // 如果最後金額<=0, 先回傳null
        }

        // 先問餘額
        $balanceFeedback = $this->baseMultiWalletApi->getBalance($transferParam->member);
        $oldBalance      = $balanceFeedback->balance;

        $memberWalletLog  = null;
        $transferFeedback = null;

        DB::connection('write')->beginTransaction();

        try {
            // 沒有要使用, 單純拿來鎖
            $platformActive = MemberPlatformActive::on('write')->lockForUpdate()->where('platform_id', $this->platform->id)->where('member_id', $member_id)->first();

            // 無差別先扣款
            $memberWalletLog = $this->doLogMemberWallet($member_id, -1 * $amount, LogMemberWallet::TYPE_TRANSFER_GAME);

            // 發動遊戲平台的存款連線
            $transferFeedback = $this->baseMultiWalletApi->deposit($transferParam);

            // 寫入單號
            $this->doProvidorLog(0, $transferFeedback->remote_payno, $memberWalletLog);

            DB::connection('write')->commit();

            // 把點數異動存起來
            $this->savePointLog($transferFeedback, $transferParam, $memberWalletLog, $oldBalance);
        } catch (FailException $e) {
            // 這邊回傳的是logmemberwallet的餘額不足類型的錯誤, 不需要寫入卡錢
            DB::connection('write')->rollback();

            // 直接回錯誤就好了 錯什麼回什麼
            throw $e;
        } catch (TransferException $e) {
            // 如果出現的是廠商回報不正確, 就退錢, 不用進卡錢
            DB::connection('write')->rollback();

            throw $e;
        } catch (Exception $e) {
            $errMsg = $e->getMessage();
            if ($e instanceof CurlException) {
                // 連線異常的時候, 因為無從判斷, 先把錢扣下來
                DB::connection('write')->commit();

                // 把點數異動存起來
                $this->savePointLog($transferFeedback, $transferParam, $memberWalletLog, $oldBalance);

                $errMsg .= '# ERROR -> '.$e->content;
            } else {
                DB::connection('write')->rollback();
            }
            // 基本發生錯誤就是直接要進卡錢 (現在只寫入資料庫)
            // 暫時先以修復緊急狀況, 前後金額的紀錄部分待修改
            $this->doStuckMoney($transferParam->member->member_id, StuckMoney::TYPE_DEPOSIT, StuckMoney::ERROR_TRANSFER, 0, 0, $amount, $errMsg);
        }
    }

    /**
     * 提款
     * 說明一下卡錢機制，其實不管怎麼樣，都會把錢給扣了
     * 只是會不會寫入卡錢的資料庫而已.
     *
     * @param TransferParameter $transferParam
     * @return void
     */
    public function withdraw(TransferParameter $transferParam)
    {
        $member_id = $transferParam->member->member_id;
        // 這邊注意: 只給轉整數, 因為如果碰到遊戲不是用精度運算, 可能會造成卡錢...
        $amount    = intval($transferParam->amount);
        // 原本傳入的參數也要改
        $transferParam->amount = $amount;
        if ($amount <= 0) {
            return null;    // 如果最後金額<=0, 先回傳null
        }

        // 先問餘額
        $balanceFeedback = $this->baseMultiWalletApi->getBalance($transferParam->member);
        $oldBalance      = $balanceFeedback->balance;

        $memberWalletLog  = null;
        $transferFeedback = null;

        // 這裡開始進行TX
        DB::connection('write')->beginTransaction();

        try {
            $platformActive = MemberPlatformActive::on('write')->lockForUpdate()->where('platform_id', $this->platform->id)->where('member_id', $member_id)->first();

            // 直接去和廠商溝通
            $transferFeedback = $this->baseMultiWalletApi->withdraw($transferParam);

            // 給點
            $memberWalletLog = $this->doLogMemberWallet($member_id, $amount, LogMemberWallet::TYPE_TRANSFER_WALLET);

            // 寫入請求記錄
            $this->doProvidorLog(0, $transferFeedback->remote_payno, $memberWalletLog);

            DB::connection('write')->commit();

            // 把點數異動存起來
            $this->savePointLog($transferFeedback, $transferParam, $memberWalletLog, $oldBalance);
        } catch (TransferException $e) {
            // 如果出現的是廠商回報不正確, 就退錢, 不用進卡錢
            DB::connection('write')->rollback();

            throw $e;
        } catch (Exception $e) {
            // 報錯
            DB::connection('write')->rollback();

            $errMsg = $e->getMessage();
            if ($e instanceof CurlException) {
                $errMsg .= '# ERROR -> '.$e->content;
            }

            // 任何報錯就先丟進去卡錢記錄, logmemberwallet不存在餘額不足狀況, 所以直接丟進去
            $this->doStuckMoney($transferParam->member->member_id, StuckMoney::TYPE_WITHDRAW, StuckMoney::ERROR_TRANSFER, 0, 0, $amount, $errMsg);
        }
    }

    /**
     * 將點數異動存入.
     *
     * @param TransferFeedback $transferFeedback
     * @param TransferParameter $transferParam
     * @param LogMemberWallet $memberWalletLog
     * @param float $oldBalance
     * @return void
     */
    private function savePointLog($transferFeedback, $transferParam, $memberWalletLog, $oldBalance)
    {
        try {
            $newBalance = 0;
            // 如果遊戲直接有回傳
            if ($transferFeedback && $transferFeedback->balance) {
                $newBalance = $transferFeedback->balance;
            } else {
                $balanceFeedback = $this->baseMultiWalletApi->getBalance($transferParam->member);
                $newBalance      = $balanceFeedback->balance;
            }

            // 存入前後異動點數
            if ($memberWalletLog) {
                $memberWalletLog->before_point = $oldBalance;
                $memberWalletLog->after_point  = $newBalance;
                $memberWalletLog->save();
            }
        } catch (Exception $e) {
            // 單純確保問餘額不可以報錯, 怕會影響到轉點機制, 所以不處理
        }
    }

    private function doLogMemberWallet(int $member_id, float $amount, string $type)
    {
        $memberWalletLog               = new LogMemberWallet();
        $memberWalletLog->member_id    = $member_id;
        $memberWalletLog->type         = $type;
        $memberWalletLog->type_id      = $this->platform->id;
        $memberWalletLog->change_money = $amount;
        $memberWalletLog->ip           = request()->ip();

        $memberWalletLog->saveRecord2();

        return $memberWalletLog;
    }

    private function doProvidorLog(int $rid, string $tid, LogMemberWallet $logWallet)
    {
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
     * 進入遊戲.
     *
     * @param LaunchGameParameter $LGP
     * @return LaunchGameFeedback
     */
    public function launchGame(LaunchGameParameter $LGP)
    {
        $member = Member::findOrError($LGP->member->member_id);

        $this->checkLaunchGame($LGP->gameId, $member);

        return $this->baseMultiWalletApi->launchGame($LGP);
    }

    /**
     * 同步作業.
     *
     * @param SyncReportParameter $srp
     * @return void
     */
    public function syncReport(SyncReportParameter $srp)
    {
        // 有平台是未開啟的時候 跳離
        if ($this->platform->enabled === 0) {
            return;
        }

        return $this->baseMultiWalletApi->syncReport($srp, function ($rows) use ($srp) {
            // 使用改寫的易讀版本
            // $result = $this->doSyncReports($rows, $srp);
            $result = (new SyncReportService())->sync($this->platform->key, $rows, $srp);

            // DG必須再次呼叫遊戲端, 告訴他我處理好的單.
            if ($this->platform->key == 'DG') {
                // var_dump($result->successMids);
                $this->baseMultiWalletApi->callMarkReport($result->successMids);
            }

            return $result;
        });
    }

    /**
     * 同步作業(SG兌獎).
     *
     * @param SyncReportParameter $srp
     * @return void
     */
    public function syncReportLottery(SyncReportParameter $srp)
    {
        return $this->baseMultiWalletApi->syncReportLottery($srp, $this->platform->id, function ($rows) use ($srp) {
            // return $this->doSyncReports($rows, $srp);
            return (new SyncReportService())->sync($this->platform->key, $rows, $srp);
        });
    }

    private function doStuckMoney(int $member_id, $type, $error, $before_point, $after_point, $point, $error_message)
    {
        // 這裡應該要存入卡錢資料表
        $this->writeStuckMoney($type, $error, $member_id, $before_point, $after_point, $point, $error_message);

        // 如果是存款的話, 還是要先把他的錢扣掉
        if ($type == StuckMoney::TYPE_DEPOSIT) {
            // 錢包先扣款
            $this->modifyMemberWallet($member_id, -1 * $point, 'stuck_money', LogMemberWallet::TYPE_TRANSFER_GAME, $before_point, $after_point);
        }

        throw new StuckMoneyException('轉帳（存點）發生錯誤，請聯絡客服');
    }

    private function writeStuckMoney($type, $error, $member_id, $before_point, $after_point, $point, $error_message)
    {
        $franchisee_id = 0;

        $member = Member::select('franchisee_id')->where('id', $member_id)->first();
        if ($member) {
            $franchisee_id = $member->franchisee_id;
        }

        $stuck_money = new StuckMoney();

        $stuck_money->franchisee_id = $franchisee_id;
        $stuck_money->platform_id   = $this->platform->id;
        $stuck_money->type          = $type;
        $stuck_money->member_id     = $member_id;
        $stuck_money->before_point  = $before_point;
        $stuck_money->after_point   = $after_point;
        $stuck_money->point         = $point;
        $stuck_money->status        = StuckMoney::STATUS_PENDING;
        $stuck_money->error         = $error;
        $stuck_money->error_message = $error_message;
        $stuck_money->agent_id      = user()->model()->id;

        $stuck_money->save();
    }
}
