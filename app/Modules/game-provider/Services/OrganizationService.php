<?php

namespace GameProvider\Services;

use App\Models\Agent;
use App\Models\Franchisee;
use App\Models\GamePlatform;
use App\Models\Member;
use App\Models\MemberPlatformActive;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Single\Api\STG;

class OrganizationService
{
    /**
     * 新增加盟主到遊戲.
     *
     * @param string $name
     * @return void
     */
    public static function addFranchisee(string $name)
    {
        $lv = 6;

        // 注意這個名稱可能會有空白，要排除
        $acc = str_replace(' ', '', $name);

        if (config('app.stg_special') === true) {
            static::doAddFranchisee('MG', $acc, $lv);
        }

        // 取得所有需要追加組織線的平台代碼
        $codes = config('app.AUTO_ORGANIZATION_PLATFORM');

        foreach ($codes as $code) {
            static::doAddFranchisee($code, $acc, $lv);
        }
    }

    /**
     * 新增加盟主到遊戲.
     *
     * @param string $platform_key
     * @param string $acc
     * @param int $lv
     * @return void
     */
    private static function doAddFranchisee(string $platform_key, string $acc, int $lv)
    {
        $platform = GamePlatform::where('key', $platform_key)->first();
        $service  = $platform->getPlatformModule();
        // $stg = new STG(json_decode($platform->setting, true));

        if (\Config::get('app.STG_MULTI') == true) {
            $service->addManageAccount('', $lv, $acc, $acc);
        } else {
            $stg = new STG(json_decode($platform->setting, true));
            $stg->addManageAccount($acc, $lv, $acc, $acc);
        }
    }

    /**
     * 新增代理到遊戲去.
     *
     * @param Agent $agent 需要新增的代理
     * @param Agent $parentAg 代理的上層
     * @return void
     */
    public static function addAgent(Agent $agent, $parentAg)
    {
        // 定義是哪個等級
        $lv = 0;
        switch ($agent->level) {
            case 1:
                $lv = 6;
                break;
            case 2:
                $lv = 5;
                break;
            case 3:
                $lv = 4;
                break;
            case 4:
                $lv = 3;
                break;
            case 5:
                $lv = 2;
                break;
        }

        // 如果是第五層（應該要用加盟商名稱）
        $acc = '';
        if ($parentAg) {
            $acc = $parentAg->account;
        }

        // if ($lv === 5) {
        //     $franchisee = Franchisee::select('id', 'name')->where('id', $agent->franchisee_id)->first();
        //     $acc        = str_replace(' ', '', $franchisee->name);
        // }

        // 反波用
        if (config('app.stg_special') === true || config('app.MG_SPECIAL') === true) {

            // 排除加盟主，不應該從這裡加
            $platform = GamePlatform::where('key', 'MG')->first();
            // $stg      = new STG(json_decode($platform->setting, true));
            if (\Config::get('app.STG_MULTI') == true) {
                $stg = $platform->getPlatformModule();
                $stg->addManageAccount($acc, $lv, $agent->account, $agent->name);
            } else {
                $stg = new STG(json_decode($platform->setting, true));
                $stg->addManageAccount($acc, $lv, $agent->account, $agent->name);
            }
        }

        // 取得所有需要追加組織線的平台代碼
        $codes = config('app.AUTO_ORGANIZATION_PLATFORM');

        if (! $codes) {
            return;
        }

        foreach ($codes as $code) {
            // 排除加盟主，不應該從這裡加
            $platform = GamePlatform::where('key', $code)->first();

            if (! $platform) {
                continue;
            }
            $service  = $platform->getPlatformModule();
            $service->addManageAccount($acc, $lv, $agent->account, $agent->account);
        }
    }

    /**
     * 新增會員到遊戲去.
     *
     * @param Member $member 會員
     * @param string $password 會員的密碼
     * @param bool $needActive 需不需要啟用會員遊戲
     * @return void
     */
    public static function addMember(Member $member, string $password, bool $needActive = true)
    {
        // stg 照舊, 怕影響到現有的版本
        if (config('app.stg_special') === true) {
            static::addMemberActive($member, 'MG', $password, $needActive);
        }

        // 取得所有需要追加組織線的平台代碼
        // $codes = config('app.AUTO_ORGANIZATION_PLATFORM');

        // if (! $codes) {
        //     return;
        // }

        // foreach ($codes as $code) {
        //     static::addMemberActive($member, $code, $password, $needActive);
        // }
    }

    /**
     * 直接往遊戲丟會員
     *
     * @param Member $member
     * @param string $platform_key
     * @param string $password
     * @param bool $needActive
     * @return void
     */
    private static function addMemberActive(Member $member, string $platform_key, string $password, bool $needActive)
    {
        $platform = GamePlatform::where('key', $platform_key)->first();

        if (! $platform) {
            return;
        }

        if ($needActive) {
            // 加入memberactive
            $active              = new MemberPlatformActive();
            $active->member_id   = $member->id;
            $active->platform_id = $platform->id;
            $active->enabled     = 1;
            $active->username    = $member->account;
            $active->setPassword($password);
            $active->generatePlayerId($platform->generatorMemberUsername($member->account), ! $platform->use_password);
            $active->saveOrError();
        }

        $memberParam            = new MemberParameter();
        $memberParam->member_id = $member->id;
        $memberParam->username  = $member->account;
        $memberParam->playerId  = $active->player_id;
        $memberParam->password  = $active->getPassword();

        if ($platform_key == 'MG') {
            if (\Config::get('app.STG_MULTI') == true) {
                $stg = $platform->getPlatformModule();
                $stg->createMember($memberParam);
            } else {
                $stg = new STG(json_decode($platform->setting, true));

                // 碰到STG的時候 帳密一定要是真的, 因為要回來登入, 不然會登不進來
                $memberParam->player_id = $member->account;
                $memberParam->password  = $password;

                $stg->addMemberAccount($memberParam);
            }

            return;
        }

        $module = $platform->getPlatformModule();
        $module->createMember($memberParam);
    }
}
