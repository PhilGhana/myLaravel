<?php

namespace App\Jobs;

use App\Models\GamePlatform;
use App\Models\LogMemberWallet;
use App\Models\LogProviderActionRecord;
use App\Models\LogProviderTransaction;
use App\Models\Report;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Services\STGService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;

use DB;

class STGJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200;

    private $allowMethod = ['doOrderRESettle', 'doOrderSettle'];

    private $method    = '';
    private $data      = null;
    private $requestid = '';
    private $gid       = '';
    private $orderdate = '';
    private $cr        = '';
    private $hr        = '';
    private $platform  = null;

    public function __construct($method, $requestid, $data = null, $gid = '', $orderdate = '', $cr = '', $hr = '')
    {
        $this->data      = $data;
        $this->method    = $method;
        $this->requestid = $requestid;
        $this->gid       = $gid;
        $this->orderdate = $orderdate;
        $this->cr        = $cr;
        $this->hr        = $hr;
        $this->platform  = GamePlatform::where('key', 'MG')->first();

    }

    public function handle()
    {
        // 如果沒有提供是要執行什麼方法, 或方法不對, 不執行
        if(!in_array($this->method, $this->allowMethod))
        {
            return;
        }

        // 開獎
        if($this->method === 'doOrderSettle')
        {
            $this->doOrderSettle();

            return;
        }

        // 重新開獎
        if($this->method === 'doOrderRESettle')
        {
            DB::beginTransaction();

            try {
                $this->doOrderRESettle();
            } catch (\Exception $e) {
                DB::rollback();
            }

            DB::commit();

            return;
        }
    }

    private function doOrderSettle()
    {
        DB::beginTransaction();

        try {
            // $gid = $this->gid;

            // 把所有的單撈出來鎖 lockForUpdate
            // Report::whereHas('detail', function($query) use ($gid) {
            //     $query->where('table', '=', $gid);
            // })->where('platform_id', $this->platform->id)
            // ->lockForUpdate()->get();

            $STGservice = new STGService(json_decode($this->platform->setting, true));

            $format = 'Y-m-d H:i:s';
            $time   = date($format);

            $srp = new SyncReportParameter();
            $srp->startAt = $time;
            $srp->endAt = $time;

	    //if($this->gid == '1005989')
	    //{
	    //    return;
	    //}

            $STGservice->doSyncReportsSTG($this->data, $srp, $this->requestid, true);

            // 要自己再對輸的單
            $STGservice->doSettleNotWin($this->gid, true, $time, $this->orderdate, $this->cr, $this->hr);

            // 寫入遊戲場次 避免到時候無法重新計算
            LogProviderActionRecord::where('request_id', $this->requestid)
                ->update([
                    'gid' => $this->gid
                ]);

        } catch (\Exception $e) {
            DB::rollback();
        }

        DB::commit();

        // 計算流水
        Artisan::call('recal:waterGid', ['gid' => $this->gid]);
    }

    private function doOrderRESettle()
    {
        $requestid = $this->requestid;

        $STGservice = new STGService(json_decode($this->platform->setting, true));

        $connection = 'write_log';

        // 撈出所有變更單號 鎖定 lockForUpdate
        $logProviders = LogProviderTransaction::on($connection)->with(['log'])->where('transaction_id', $requestid)
                        ->where('platform_id', $this->platform->id)
                        ->lockForUpdate()
                        ->get();

        $logActionRecord = LogProviderActionRecord::on($connection)->where('request_id', $requestid)->first();
        // 場次代碼
        $gid = $logActionRecord->gid;
        // 未異動單號
        $not_rollback_report_ids = [];

        foreach($logProviders as $logProvider)
        {
            // 之前給錢的log
            $logWallet = $logProvider->log;

            // 這次要處理的log
            $rlog            = new LogMemberWallet();
            $rlog->member_id = $logWallet->member_id;
            $rlog->type      = LogMemberWallet::TYPE_ROLLBACK;
            $rlog->type_id   = $logWallet->id;

            DB::beginTransaction();

            try {

                // 如果有發放 正的為發放 負的不管本來就該扣
                // 先收錢 收不到錢的話 就不要繼續了 不然會重複給錢
                // 只要住單的狀態不變成 STATUS_BETTING 就不會重複對獎
                if($logWallet->change_money > 0)
                {
                    // 把之前的中獎金額全部拿回來
                    $rlog->change_money = $logWallet->change_money * -1;

                    // 扣錢
                    // 收不到錢會跳走
                    $STGservice->logTransaction($logProvider->report_id, $requestid, $rlog);
                }

                // 修正狀態 從已結算變成投注中 這樣才能重新兌獎
                $update_report_count = Report::where('id', $logWallet->type_id)
                    ->where('status', Report::STATUS_COMPLETED)
                    ->update([
                        'valid_amount' => 0,
                        'win_amount'   => 0,
                        'status'       => Report::STATUS_BETTING
                ]);

                // 如果沒有任何一筆被更新，就是做過了
                if($update_report_count === 0)
                {
                    DB::commit();
                    continue;
                }

                // report 拿回來重算
                $report = Report::findOrError($logWallet->type_id);
                $report->updateTotal();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();

                $not_rollback_report_ids[] = $logWallet->type_id;

                // 如果發生錢扣不下去,可能是會員沒錢,要記錄起來
                $STGservice->doRecordResetMoneyError($logWallet->member_id, $logWallet->change_money, $logWallet->type_id, $requestid);

            } catch (\Throwable $e) {
                DB::rollback();

                $not_rollback_report_ids[] = $logWallet->type_id;

                // 如果發生錢扣不下去,可能是會員沒錢,要記錄起來
                $STGservice->doRecordResetMoneyError($logWallet->member_id, $logWallet->change_money, $logWallet->type_id, $requestid);

            }
        }

        // 把這個GID的單 全部變更成下注中 準備重新對獎
        Report::whereHas('detail', function($query) use ($gid) {
            $query->where('table', $gid);
        })
        ->where('platform_id', $this->platform->id)
        ->whereNotIn('id', $not_rollback_report_ids)
        ->where('status', Report::STATUS_COMPLETED)
        ->update(['status' => Report::STATUS_BETTING]);
    }

}

