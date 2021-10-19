<?php


namespace App\Listeners;


use App\Services\Role\RoleViewService;
use Illuminate\Events\Dispatcher;
use App\Events\ViewUpdated;


class ReloadRoleView
{


    public function onViewUpdated ($event)
    {
        # 刪除所有 redis 中的 view 資料快取
        $key = RoleViewService::REDIS_ROLE_VIEW_KEY;
        $ids = redis()->hKeys($key);
        redis()->hDel($key, ...$ids);
    }

    public function subscribe (Dispatcher $events) {
        $events->listen(ViewUpdated::class, 'App\Listeners\ReloadRoleView@onViewUpdated');
    }

}
