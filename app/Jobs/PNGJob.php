<?php

namespace App\Jobs;

use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PNGJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 玩家登入. 跳過.
     */
    const CasinoPlayerLogin              = 1;
    /**
     * 玩家登出. 跳過.
     */
    const CasinoPlayerLogout             = 2;
    /**
     * 玩家投注.
     */
    const CasinoTransactionReserve       = 3;
    /**
     * 玩家正再遊玩中.
     */
    const CasinoTransactionReleaseOpen   = 4;
    /**
     * 玩家結束遊玩後.
     */
    const CasinoTransactionReleaseClosed = 5;
    /**
     * 玩家獲得大獎.
     * CasinoTransactionReleaseClosed 會回傳, 不需要太即時, 跳過.
     */
    const CasinoJackpotRelease           = 6;
    /**
     * 玩家免費遊戲結束
     * CasinoTransactionReleaseClosed 會回傳,不需要那麼即時, 跳過這個訊號
     */
    const CasinoFreegameEnd              = 7;
    /**
     * 玩家開始遊戲. 跳過.
     */
    const CasinoGamesSessionOpen         = 8;
    /**
     * 玩家達成成就. 跳過.
     */
    const CasinoAchievementTriggered     = 9;

    public $timeout = 1200;

    /**
     * 傳送過來的消息.
     *
     * @var array
     */
    private $messages;

    public function __construct($msgs)
    {
        $this->messages = $msgs;
    }

    public function handle()
    {
        dd($this->messages);

        foreach ($this->messages as $msg) {

            // 排除不必要的消息
        }
    }
}
