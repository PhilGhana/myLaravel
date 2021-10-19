<?php

namespace App\Providers;

use App\Exceptions\ErrorException;
use App\Exceptions\UnauthorizedException;
use App\Models\Agent;
use App\Models\AgentIpWhitelist;
use App\Providers\SyncServiceProvider;

class UserServiceProvider
{
    const SESSION_MAP_KEY = 'session-map';

    private $token;

    private $sessionMode = false;

    protected $cacheData = [];

    protected $lifeTimeMin;

    protected $agentModel;

    public function __construct($token = null)
    {
        $this->sessionMode = config('app.session_mode');
        $this->startSession($token);

        $referer = request()->server('HTTP_REFERER');
        if ($referer) {
            $this->put('referer', $referer);
        }
    }

    public function getMapKey()
    {
        return static::SESSION_MAP_KEY.':'.$this->get('id');
    }

    private function registerLoggedin(int $id, string $token)
    {
        $key = static::SESSION_MAP_KEY.":{$id}";

        // 若設定為單一登入時, 先踢除其他登入者
        if (config('app.user_unique')) {
            redis()->del($key);
        }
        $data = [
            'id'    => $id,
            'token' => $token,
        ];
        redis()->hSet($key, $token, json_encode($data));
        $this->expire();
    }

    /**
     * 執行登入註冊.
     *
     * @param Agent $agent 登入者的 agent model
     * @return void
     */
    public function onLoggedIn(Agent $agent)
    {
        $useIPWhitelist = $agent->ip_whitelist === 1;
        $ips            = ['*'];
        if ($useIPWhitelist) {
            $ips = AgentIpWhitelist::select('ip')
                ->where('agent_id', $agent->id)
                ->get()
                ->pluck('ip')
                ->all();
        }

        $this->mput([

            'id' => $agent->id,

            // 角色 id (role.id)
            'rid' => $agent->role_id,

            'account' => $agent->account,

            'name' => $agent->name,

            'ip-whitelist' => $ips,

        ]);
        $this->registerLoggedin($agent->id, $this->token);
    }

    public function token()
    {
        return $this->sessionMode ? session()->getId() : $this->token;
    }

    public function startSession($token = null)
    {
        if ($this->sessionMode) {
            $sid = session()->getId();
            if ($token && $sid !== $token) {
                session()->setId($token);
                session()->start();
                $this->token = $token;
            } else {
                $this->token = $sid;
            }
        } else {
            $this->token     = $token ?: sha1(microtime().rand());
            $this->cacheData = json_decode(redis('session')->get($token) ?: '[]', true);
        }

        $this->lifeTimeMin = config('session.lifetime', 1);
    }

    /**
     * 是否已登入.
     *
     * @return bool
     */
    public function isLogin()
    {
        $id    = $this->get('id');
        $token = $this->token;
        if ($id && redis()->hExists($this->getMapKey(), $token)) {
            $this->expire();

            return true;
        }

        return false;
    }

    public function ipWhitelist()
    {
        return $this->get('ip-whitelist') ?: [];
    }

    public function checkIP()
    {
        $ip        = request()->ip();
        $whiteList = $this->ipWhitelist();
        foreach ($whiteList as $rule) {
            if ($rule === '*') {
                return true;
            }

            $regRule = str_replace('*', '\w+', $rule);
            if (preg_match("/^{$regRule}$/", $ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 取得登入者的 agent model.
     *
     * @return \App\Models\Agent
     */
    public function model()
    {
        $id = $this->get('id');
        if (! $this->agentModel) {
            $this->agentModel = Agent::find($id);
        }
        if ($this->isLogin() && ! $this->agentModel) {
            $this->logout();
            throw new UnauthorizedException();
        }

        return $this->agentModel;
    }

    /**
     * 設定存活時間 (依 env.SESSION_LIVETIME).
     *
     * @return void
     */
    protected function expire()
    {
        $lifeTime = $this->lifeTimeMin * 60000;
        redis()->expire($this->getMapKey(), $lifeTime);
        redis('session')->expire($this->token, $lifeTime);
    }

    /**
     * 執行登出 (清空 session ).
     *
     * @return void
     */
    public function logout()
    {
        if ($this->sessionMode) {
            session()->flush();
        } else {
            redis()->hDel($this->getMapKey(), $this->token);
            redis('session')->del($this->token);
            $this->cacheData = [];
        }
    }

    public function put($key, $value)
    {
        if ($this->sessionMode) {
            session()->put($key, $value);
        } else {
            $this->cacheData[$key] = $value;
            redis('session')->set($this->token, json_encode($this->cacheData));
            $this->expire();
        }

        return $this;
    }

    public function mput($arr)
    {
        if ($this->sessionMode) {
            foreach ($arr as $key => $val) {
                $this->put($key, $val);
            }
        } else {
            $this->cacheData = array_merge($this->cacheData, $arr);
            redis('session')->set($this->token, json_encode($this->cacheData));
            $this->expire();
        }

        return $this;
    }

    public function get($key, $default = null)
    {
        if ($this->sessionMode) {
            return session($key, $default);
        } else {
            return $this->cacheData[$key] ?? $default;
        }
    }

    /*
     * 檢查該角色是否可以看到完整的會員的基本資料.
     * (主用是用在審核操作的檢查).
     *
     * @return void
     */
    // public function checkFullInfo()
    // {
    //     // 審核不再檢查部分權限
    //     if (! (
    //         $this->model()->role->full_info == 1 ||
    //         $this->model()->role->full_info == '1'
    //     )) {
    //         throw new ErrorException(__('review.not_full_info'));
    //     }
    // }
}
