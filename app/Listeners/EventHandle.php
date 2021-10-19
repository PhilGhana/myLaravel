<?php


namespace App\Listeners;


use App\Events\RoleApiUpdated;
use App\Events\ViewUpdated;
use App\Events\ThrowException;
use App\Events\ReviewUpdated;
use Illuminate\Events\Dispatcher;
use App\Events\MemberUpdated;
use App\Events\AgentUpdated;
use App\Events\RoleUpdated;
use App\Events\LetterMessageUpdated;
use App\Events\SendAnnouncement;
use App\Events\MarqueeUpdated;
use App\Events\MultiWalletStuckLog;

class EventHandle
{
    public function subscribe(Dispatcher $events)
    {

        $events->listen(ThrowException::class, 'App\Listeners\HandleException@handle');


        $events->listen(RoleApiUpdated::class, 'App\Listeners\HandleUpdateRedisData@clearRoleApi');
        $events->listen(ViewUpdated::class, 'App\Listeners\HandleUpdateRedisData@clearRoleView');
        # 跑馬燈更新
        $events->listen(MarqueeUpdated::class, 'App\Listeners\HandleUpdateRedisData@clearMarquee');

        # 輪播圖更新


        $events->listen(ReviewUpdated::class, 'App\Listeners\HandleReview@handle');

        $events->listen(AgentUpdated::class, 'App\Listeners\HandleLogout@onAgentUpdated');
        $events->listen(RoleUpdated::class, 'App\Listeners\HandleLogout@onRoleUpdated');
        $events->listen(MemberUpdated::class, 'App\Listeners\HandleLogout@onMemberUpdated');

        $events->listen(ReviewUpdated::class, 'App\Listeners\SyncSocketService@reviewUpdated');
        $events->listen(LetterMessageUpdated::class, 'App\Listeners\SyncSocketService@messageUpdated');
        $events->listen(SendAnnouncement::class, 'App\Listeners\SyncSocketService@sendAnnouncement');

        // 監聽多錢包事件
        $events->listen(MultiWalletStuckLog::class, 'App\Listeners\HandleMultiWalletStuckLog@handle');
    }
}
