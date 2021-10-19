<?php


namespace App\Listeners;


use App\Services\Role\RoleApiService;
use App\Events\RoleApiUpdated;
use App\Services\Role\RoleViewService;

class HandleUpdateRedisData
{
    /**
     * RoleApi 資料更新
     *
     * @param RoleApiUpdated $event
     * @return void
     */
    public function clearRoleApi (RoleApiUpdated $event)
    {
        $roleId = $event->roleId;
        $roleServ = new RoleApiService($roleId);
        $roleServ->reload(true);
    }


    public function clearRoleView ($event)
    {
        # 刪除所有 redis 中的 view 資料快取
        $key = RoleViewService::REDIS_ROLE_VIEW_KEY;
        $ids = redis()->hKeys($key);
        redis()->hDel($key, ...$ids);
    }
}
