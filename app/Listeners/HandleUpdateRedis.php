<?php


namespace App\Listeners;


use App\Services\Role\RoleApiService;
use App\Events\RoleApiUpdated;
use App\Services\Role\RoleViewService;
use App\Events\MarqueeUpdated;

class HandleUpdateRedis
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

    /**
     * Undocumented function
     *
     * @param [type] $event
     * @return void
     */
    public function clearRoleView ($event)
    {
        # 刪除所有 redis 中的 view 資料快取
        RoleViewService::clearAll();
    }


    public function updateMarquee (MarqueeUpdated $event) {

        $marquee = $event->marquee;


    }



}
