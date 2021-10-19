<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        // 執行同步
        // 不再這裡呼叫了 通通換到系統crontab去
        // if (config('app.SYNC_MULTI') == true) {
        //     $schedule->command('sync:report')->cron('*/2 * * * *');
        // }
        $schedule->command('report:package')->dailyAt('01:00');

        $schedule->command('sync:fullpay')->cron('*/2 * * * *');
        // 執行自動日結算
        // $schedule->command('ration:daily')->dailyAt(config('app.auto_daily_at'));

        // 每分鐘確認是不是有需要自動派發退水、紅利
        // $schedule->command('ration:water')->everyMinute();
        // $schedule->command('ration:bonus')->everyMinute();

        // 每天刪除打過流水的注單紀錄
        $schedule->command('cancelCheckBetAmount')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
