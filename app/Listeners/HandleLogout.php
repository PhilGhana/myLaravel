<?php


namespace App\Listeners;

use App\Services\Role\RoleViewService;

use App\Providers\UserServiceProvider;
use Exception;
use App\Events\AgentUpdated;
use Illuminate\Events\Dispatcher;
use App\Events\ViewUpdated;
use App\Events\RoleApiUpdated;
use App\Events\RoleUpdated;
use App\Models\Agent;
use App\Events\MemberUpdated;


class HandleLogout
{

    /**
     * 移除登入者的 cache 資料，將其登出
     *
     * @param array $keys
     *          1. null    全部登出
     *          2. [...agent.id  指定登出某代理
     *          3. [ agent.id => ...tokens ] 指定登出某代理下的部分 token
     *
     * @return void
     */
    protected function logoutAgents($keys = null)
    {
        $keys = (array) $keys;
        $sessionKey = UserServiceProvider::SESSION_MAP_KEY;
        if ($keys === null) {
            $keys = redis()->keys("{$sessionKey}:*") ?: [];
        }

        foreach ($keys as $k => $v) {
            $key = "{$sessionKey}:{$k}";
            $tokens = $v;

            # $tokens 不是陣列, 而且 tokens 是數字 (狀況 2), 刪除整個陣列群
            if (!is_array($tokens) && is_numeric($tokens)) {

                $key = "{$sessionKey}:{$v}";
                redis()->del($key);

            } elseif ($tokens) {

                redis()->hDel($key, ...$tokens);
            }
        }
    }

    /**
     * 移除登入者的 cache 資料，將其登出
     *
     * @param array $keys
     *          1. null    全部登出
     *          2. [...member.id  指定登出某代理
     *          3. [ member.id => ...tokens ] 指定登出某代理下的部分 token
     *
     * @return void
     */
    protected function logoutMembers($keys = null)
    {
        $keys = (array) $keys;
        $sessionKey = UserServiceProvider::SESSION_MAP_KEY;
        if ($keys === null) {
            $keys = redis('member')->keys("{$sessionKey}:*") ?: [];
        }

        foreach ($keys as $k => $v) {
            $key = "{$sessionKey}:{$k}";
            $tokens = $v;

            # $tokens 不是陣列, 而且 tokens 是數字 (狀況 2), 刪除整個陣列群
            if (!is_array($tokens) && is_numeric($tokens)) {

                $key = "{$sessionKey}:{$v}";
                redis('member')->del($key);
            } elseif ($tokens) {

                redis('member')->hDel($key, ...$tokens);
            }
        }
    }

    public function onViewUpdated(ViewUpdated $event)
    {
        # 強制登出
        $this->logoutAgents();
    }

    public function onAgentUpdated(AgentUpdated $event)
    {
        $sessionKey = UserServiceProvider::SESSION_MAP_KEY;
        $agent = $event->agent;
        $this->logoutAgents([$agent->id]);
    }

    public function onRoleUpdated(RoleUpdated $event)
    {

        $role = $event->role;
        $ids = Agent::select('id')
            ->where('role_id', $role->id)
            ->get()
            ->pluck('id')
            ->all();
        $this->logoutAgents($ids);
    }

    public function onMemberUpdated(MemberUpdated $event)
    {
        $member = $event->member;
        $this->logoutMembers([$member->id]);
    }

    /**
     * 註冊監聽器
     *
     * @param Dispatcher $events
     * @return void
     */
    public function subscribe(Dispatcher $events)
    {

        $events->listen(AgentUpdated::class, 'App\Listeners\LogoutUser@onAgentUpdated');

        // $events->listen(ViewUpdated::class, 'App\Listeners\LogoutUser@onViewUpdated');
        $events->listen(RoleUpdated::class, 'App\Listeners\LogoutUser@onRoleUpdated');

        $events->listen(MemberUpdated::class, 'App\Listeners\LogoutUser@onMemberUpdated');
    }
}
