<?php

namespace App\Models;

use App\Observers\AgentObserver;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

/**
 * @property int $id ID
 * @property int $franchisee_id
 * @property int $extend_id 繼承 agent.id 的設定, 若有值, 此即為子帳號
 * @property int $role_id 角色權限 role.id
 * @property string $account 帳號
 * @property string $password 密碼
 * @property string $name 組織名稱 (總監、大股東等...)
 * @property int $enabled 是否啟用
 * @property int $ip_whitelist 是否啟用白名單功能
 * @property int $locked 是否鎖定
 * @property int $level 所屬層級
 * @property int $lv1 上層層級 1 的 agent.id
 * @property int $lv2 上層層級 2 的 agent.id
 * @property int $lv3 上層層級 3 的 agent.id
 * @property int $lv4 上層層級 4 的 agent.id
 * @property string $invitation_code 邀請碼
 * @property int $error_count 近期登入失敗次數
 * @property string $log_login_id 登入記錄 id (log_agent_login.id)
 * @property \Illuminate\Support\Carbon $updated_at 修改時間
 * @property \Illuminate\Support\Carbon $created_at 建立時間
 * @property string $line_id
 * @property string $line_link
 *
 *
 * @property int $parentId 上層 id (getParentIdAttribute)
 * @property AgentPercentConfig $parentPercentConfig 上層手續費設定
 * @property Agent $parent 上層組織成員
 * @property Agent $extend 繼承組織成員
 * @property Role $role 所屬角色
 * @property AgentWallet $wallet
 */
class Agent extends BaseModel
{
    protected $toCamelCase = true;

    protected $table = 'agent';

    protected $hidden = ['password'];

    protected $casts = [
        'id'           => 'integer',
        'role_id'      => 'integer',
        'extend_id'    => 'integer',
        'enabled'      => 'integer',
        'ip_whitelist' => 'integer',
        'locked'       => 'integer',
        'level'        => 'integer',
        'lv1'          => 'integer',
        'lv2'          => 'integer',
        'lv3'          => 'integer',
        'lv4'          => 'integer',
        'bonus'        => 'double',
        'bonus_rank3'  => 'double',
        'bonus_rank2'  => 'double',
        'bonus_rank1'  => 'double',
        'error_count'  => 'integer',
        'log_login_id' => 'integer',
    ];

    public static function boot()
    {
        static::observe(AgentObserver::class);
    }

    public function checkPassword($password)
    {
        return Hash::check($password, $this->password);
    }

    /**
     * 上層 id.
     */
    public function getParentIdAttribute()
    {
        return ([
            0,
            $this->lv1,
            $this->lv2,
            $this->lv3,
            $this->lv4,
        ])[$this->level - 1] ?? 0;
    }

    /**
     * 取得所有上層 id.
     *
     * @return int[]
     */
    public function getAllParnetIds()
    {
        $ids = [
            $this->lv1,
            $this->lv2,
            $this->lv3,
            $this->lv4,
        ];

        return array_slice($ids, 0, $this->level - 1);
    }

    public function getUpperLevelAttribute()
    {
        return $this->level - 1;
    }

    public function setPassword($password)
    {
        $this->password = Hash::make($password);
    }

    /**
     * 是否為公司帳號
     *
     * @return bool
     */
    public function isCompany()
    {
        return $this->level === 0 && $this->extend_id === 0;
    }

    public function isAgent()
    {
        return ! $this->isCompany() && ! $this->isSubAgent();
    }

    /**
     * 此角色可以使用的加盟商 id, 若傳入值是不可用的, 則回傳自身的加盟商 id.
     *
     * @param int $fid
     * @return int
     */
    public function allowFranchiseeId(int $fid = 0)
    {
        if ($this->isCompany()) {
            return $fid;
        }

        return $this->isSubAgent()
        ? $this->extend->franchisee_id
        : $this->franchisee_id;
    }

    /**
     * 是否為子帳號
     *
     * @return bool
     */
    public function isSubAgent()
    {
        return ! empty($this->extend_id);
    }

    public function isDisabled(bool $deep = true)
    {
        if ($this->enabled !== 1) {
            return true;
        }

        if ($deep) {
            if (! $this->role || $this->role->isDisabled()) {
                return true;
            }
        }

        return false;
    }

    public function isLocked()
    {
        return $this->locked === 1;
    }

    /**
     * 建立代理的邀請碼
     *
     * @return void
     */
    public function generatorInvitationCode()
    {
        // $this->invitation_code = 'A'.mb_substr(Uuid::uuid4(), 0, 7);
    }

    /**
     * 檢查上下層關係
     * @param Agent $otherAgent
     * @return bool
     */
    public function parentHas($otherAgent)
    {
        $upperLv = [];
        array_push($upperLv, $this->lv1, $this->lv2, $this->lv3, $this->lv4);

        return in_array($otherAgent->id, $upperLv);
    }

    /**
     * 檢查下層是否包含某代理.
     *
     * @param Agent $otherAgent
     * @return bool
     */
    public function childrenHas(self $otherAgent)
    {
        return in_array($this->id, [
            $otherAgent->lv1,
            $otherAgent->lv2,
            $otherAgent->lv3,
            $otherAgent->lv4,
        ]);
    }

    /**
     * 檢查對象是否為自己的子帳號
     *
     * @param Agent $agent
     * @return bool
     */
    public function subHas(self $agent)
    {
        if ($this->isSubAgent() || ! $agent->isSubAgent()) {
            return false;
        }

        return $this->id && ($this->id === ($agent->extend->id ?? null));
    }

    /**
     * 取 lv{$level - 1} 的 id.
     *
     * @return int
     */
    public function getUpperAgent()
    {
        $upperLv = $this->UpperLevel;
        $lv      = "lv{$upperLv}";
        $upperAg = $this->$lv;

        return $upperAg;
    }

    /**
     * 取得上層組織線.
     *
     * @return Agent[]
     */
    public function parents()
    {
        $pids = $this->getAllParnetIds();

        return static::whereIn('id', $pids)->orderBy('level')->get();
    }

    public function parent()
    {
        return $this->hasOne(self::class, 'id', 'parentId');
    }

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }

    public function platformConfigs()
    {
        return $this->hasMany(AgentPlatformConfig::class, 'agent_id', 'id');
    }

    public function wallet()
    {
        return $this->hasOne(AgentWallet::class, 'id', 'id');
    }

    public function extend()
    {
        return $this->hasOne(self::class, 'id', 'extend_id');
    }

    // 子帳號筆數
    public function numSubs()
    {
        return $this->hasOne(self::class, 'extend_id', 'id')
            ->selectRaw('extend_id, count(1) AS total')
            ->groupBy('extend_id');
    }

    public function numChildrenMembers()
    {
        return $this->hasOne(Member::class, 'alv5', 'id')
            ->selectRaw('count(*) as count');
    }

    public function numChildrenAgents()
    {
        return $this->hasOne(self::class, "lv{$this->level}", 'id')
            ->selectRaw("lv{$this->level}, count(*) as count");
    }

    public function childrenAgents()
    {
        return $this->hasOne(self::class, "lv{$this->level}", 'id');
    }

    // 所屬會員
    public function memberWallet()
    {
        return $this->hasMany(Member::class, "alv{$this->level}", 'id')
            ->with('wallet');
    }

    public function agentPercentConfig()
    {
        return $this->hasOne(AgentPercentConfig::class, 'id', 'id');
    }

    public function parentPercentConfig()
    {
        return $this->hasOne(AgentPercentConfig::class, 'id', 'parentId');
    }

    /**
     * 取得手續費的設定上限比例.
     *
     * @return float
     */
    public function maxFeePercent()
    {
        $pid = $this->parentId;
        if ($pid) {
            $config = AgentPercentConfig::find($pid);

            return $config->fee_percent ?? 0;
        }

        return 100;
    }

    public function invitationUrl(bool $httpSchema = false)
    {
        $franchisee = $this->franchisee;
        if (! $franchisee) {
            return [];
        }
        $hosts   = array_column(json_decode($franchisee->host ?? '[]', true) ?? [], 'url');
        $defualt = config('app.default_promo_host', null);
        if ($defualt && ! in_array($defualt, $hosts)) {
            $hosts[] = $defualt;
        }

        // 繼承父帳號的邀請碼
        $codes = $this->extend ? $this->extend->invitationCodes : $this->invitationCodes;
        $urls  = [];
        foreach ($codes as $code) {
            $str = $code->getUrlStr();
            foreach ($hosts as $host) {
                $urls[] = ($httpSchema ? 'https://' : '').$host.$str;
            }
        }

        return $urls;
    }

    public function franchisee()
    {
        return $this->hasOne(Franchisee::class, 'id', 'franchisee_id');
    }

    public function log()
    {
        return $this->hasOne(LogAgentLogin::class, 'id', 'log_login_id')
            ->withDefault();
    }

    public function invitation()
    {
        return $this->hasMany(InvitationHost::class, 'agent_id', 'id');
    }

    /**
     * 邀請碼
     *
     * @return void
     */
    public function invitationCodes()
    {
        return $this->hasMany(AgentInvitationCode::class, 'agent_id', 'id');
    }

    /**
     * 舊版的邀請碼
     * 覆寫屬性，避免噴錯誤.
     *
     * @return void
     */
    public function getInvitationCodeAttribute()
    {
        $codes = $this->invitationCodes;

        return $codes->isNotEmpty() ? $codes->first()->code : null;
    }

    public function agentSite()
    {
        return $this->hasOne(AgentSite::class, 'agent_id', 'id');
    }
}