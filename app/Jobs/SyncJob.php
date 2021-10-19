<?php

namespace App\Jobs;

use Exception;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Services\MultiWalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $platform;

    private $stime;

    private $etime;

    public function __construct($platform, $stime, $etime)
    {
        $this->platform = $platform;
        $this->stime    = $stime;
        $this->etime    = $etime;
    }

    public function handle()
    {
        // 不管怎樣都不可以讓服務死掉, 有問題就記錄起來
        try {
            // SG要特別處理
            if ($this->platform->key === 'SG') {
                $result = $this->multiWalletSyncLottery($this->platform, $this->stime, $this->etime);

                // 有開獎資料，就不要再對了
                if ($result->total > 0) {
                    return;
                }
            }

            $lastTime = $this->stime;
            $end_at   = $this->etime;

            // BNG和PLS需要的時間格式不同
            if ($this->platform->key === 'bng' || $this->platform->key === 'pls') {
                $lastTime = $this->stime.'+08:00';
                $end_at   = $this->etime.'+08:00';
            }

            $this->multiWalletSync($this->platform, $lastTime, $end_at);
        } catch (Exception $e) {
            Log::channel('synclog')->emergency('SyncReport:'.$this->platform->key . ' ' . $e->getMessage());
        }
    }

    private function multiWalletSync($platform, $stime, $etime)
    {
        $module = $platform->getPlatformModule();

        $service = new MultiWalletService($module, $platform);

        $syncParams          = new SyncReportParameter();
        $syncParams->startAt = $stime;
        $syncParams->endAt   = $etime;
        $syncParams->status  = '1';

        return $service->syncReport($syncParams);
    }

    private function multiWalletSyncLottery($platform, $stime, $etime)
    {
        $module = $platform->getPlatformModule();

        $service = new MultiWalletService($module, $platform);

        $syncParams          = new SyncReportParameter();
        $syncParams->startAt = $stime;
        $syncParams->endAt   = $etime;
        $syncParams->status  = '1';

        return $service->syncReportLottery($syncParams);
    }
}
