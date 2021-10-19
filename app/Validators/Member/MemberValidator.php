<?php

namespace App\Validators\Member;

use App\Rules\StringRegex;
use App\Rules\StringSymbolRegex;
use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\SortArray;
use Illuminate\Validation\Rule;

class MemberValidator extends BaseValidator
{
    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:member,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkToggleLocked($data)
    {
        (new static($data, [
            'id'     => 'required|exists:member,id',
            'locked' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkUpdateTag($data)
    {
        (new static($data, [
            'id'   => 'required|exists:member,id',
            'tags' => 'nullable|array|exists:member_tag,id',
        ]))->check();
    }

    public static function checkBindTag($data)
    {
        (new static($data, [
            'memberId' => 'required|exists:member,id',
            'tagId'    => 'required|integer|exists:member_tag,id',
        ]))->check();
    }

    public static function checkUnbindTag($data)
    {
        (new static($data, [
            'memberId' => 'required|exists:member,id',
            'tagId'    => 'required|integer|exists:member_tag,id',
        ]))->check();
    }

    public static function checkRemoveTag($data)
    {
        (new static($data, [
            'id'    => 'required|exists:member,id',
            'tagId' => 'required|exists:member_tag,id',
        ]))->check();
    }

    public static function checkSetClubRank($data)
    {
        (new static($data, [
            'id'         => 'required|exists:member,id',
            'clubRankId' => 'required|exists:club_rank,id',
        ]))->check();
    }

    public static function checkGetTags($data)
    {
        (new static($data, [
            'id'    => 'required|integer|min:0',
            'name'  => ['required', 'string', new StringRegex],
            'color' => 'required|string',
        ]))->check();
    }

    public static function checkGetList($data)
    {
        (new static($data, [
            'franchiseeId' => 'nullable|integer',
            'account'      => ['nullable', 'string', new StringRegex],
            'name'         => ['nullable', 'string', new StringRegex],
            'enabled'      => 'nullable|in:-1,0,1',
            'locked'       => 'nullable|in:-1,0,1',
            'clubId'       => 'nullable|integer|min:0',
            'clubRankId'   => 'nullable|integer|min:0',
            'tagId'        => 'nullable|integer|min:0',
            'parentId'     => 'nullable|numeric|exists:agent,id',
            'sorts'        => ['nullable', new SortArray(['id', 'account', 'name', 'createdAt'])],
            'page'         => 'nullable|integer|min:1',
            'perPage'      => 'nullable|integer|min:1',
        ]))->check();
    }

    public static function checkAdd($data)
    {
        $level = 5; // 上層代理層級

        (new static($data, [
            'parentId'   => [
                'required',
                Rule::exists('agent', 'id')->where(function ($query) use ($level) {
                    $query->where('level', $level);
                }),
            ],
            'account'    => ['required', 'string', 'min:6', 'max:30', new StringRegex(StringRegex::TYPE_MEMBER_ACCOUNT)],
            'password'   => ['required', 'string', 'min:6', 'max:40', 'regex:/^[0-9a-zA-Z\@\.\_\$\#\%\^\&\*\+\-]+$/'],
            'phone'      => ['required', 'string', 'min:10', 'max:10', 'regex:/^[0-9\-]+$/'],
            'clubRankId' => 'required|exists:club_rank,id',
            'name'       => ['required', 'string', 'max:20', new StringRegex],
            'nickname'   => ['nullable', 'string', 'max:20', new StringRegex],
            'birth'      => 'nullable|date',
            'gender'     => 'required|in:M,F,NA',
            'email'      => 'nullable|email|max:80',
            'qq'         => ['nullable', 'string', 'max:30', 'regex:/^[0-9a-zA-Z\@\.\_]+$/'],
            'wechat'     => ['nullable', 'string', 'max:30', 'regex:/^[0-9a-zA-Z\@\.\_]+$/'],
            'weibo'      => ['nullable', 'string', 'max:30', 'regex:/^[0-9a-zA-Z\@\.\_]+$/'],
            // 'enabled'    => 'required|in:0,1',
            // 'locked'     => 'required|in:0,1',
        ]))->check();
    }

    public static function checkEdit($data)
    {
        $valids = [
            'id'         => 'required|exists:member,id',
            'clubRankId' => 'required|exists:club_rank,id',
            'gender'     => 'required|in:M,F,NA',
            // 'enabled'    => 'required|in:0,1',
            // 'locked'     => 'required|in:0,1',
        ];

        // 資料含有*號 不寫到資料庫 所以不檢查
        if (! preg_match("/\*/i", $data['name'])) {
            $valids['name'] = ['required', 'string', 'max:20', new StringRegex];
        }

        if (! preg_match("/\*/i", $data['nickname'])) {
            $valids['nickname'] = ['nullable', 'string', 'max:20', new StringRegex];
        }

        if (! preg_match("/\*/i", $data['phone'])) {
            $valids['phone'] = ['required', 'string', 'min:10', 'max:10', 'regex:/^[0-9\-]+$/'];
        }

        if (! preg_match("/\*/i", $data['birth'])) {
            $valids['birth'] = 'nullable|date';
        }

        if (! preg_match("/\*/i", $data['email'])) {
            $valids['email'] = 'nullable|string|max:80|email';
        }

        if (! preg_match("/\*/i", $data['qq'])) {
            $valids['qq'] = ['nullable', 'string', 'max:30', 'regex:/^[0-9a-zA-Z\@\.\_]+$/'];
        }

        if (! preg_match("/\*/i", $data['wechat'])) {
            $valids['wechat'] = ['nullable', 'string', 'max:30', 'regex:/^[0-9a-zA-Z\@\.\_]+$/'];
        }

        if (! preg_match("/\*/i", $data['weibo'])) {
            $valids['weibo'] = ['nullable', 'string', 'max:30', 'regex:/^[0-9a-zA-Z\@\.\_]+$/'];
        }

        (new static($data, $valids))->check();
    }

    public static function checkEditPassword($data)
    {
        (new static($data, [
            'id'       => 'required|exists:member,id',
            'password' => ['required', 'string', 'min:6', 'max:40', new StringRegex],
        ]))->check();
    }

    public static function checkEditMoney($data)
    {
        (new static($data, [
            'id'     => 'required|exists:member,id',
            'amount' => 'required|numeric',
            'remark' => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkManualEditMoney($data)
    {
        (new static($data, [
            'id'     => 'required|exists:member,id',
            'amount' => 'required|numeric',
            'remark' => ['required', 'string'],
        ]))->check();
    }

    public static function checkEditBonus($data)
    {
        (new static($data, [
            'id'     => 'required|exists:member,id',
            'amount' => 'required|numeric',
            'remark' => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkGiveMoney($data)
    {
        (new static($data, [
            'id'                  => 'required|exists:member,id',
            'amount'              => 'required|numeric|min:0',
            'bet_amount_multiple' => 'nullable|min:0',
            'remark'              => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkTakeBack($data)
    {
        (new static($data, [
            'id'     => 'required|exists:member,id',
            'amount' => 'required|numeric|min:0',
            'remark' => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkPlatformWallet($data)
    {
        (new static($data, [
            'memberId'   => 'required|exists:member,id',
            'platformId' => 'required|exists:game_platform,id',
        ]))->check();
    }

    public static function platformActives($data)
    {
        (new static($data, [
            'id' => 'required|exists:member,id',
        ]))->check();
    }

    public static function checkEditGame($data)
    {
        (new static($data, [
            'platformId' => 'required|exists:game_platform,id',
            'memberId'   => 'required|exists:member,id',
            'amount'     => 'required|numeric',
            'remark'     => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkWalletLogs($data)
    {
        (new static($data, [
            'id'        => 'required|exists:member,id',
            'startTime' => 'nullable|date_format:"Y-m-d H:i:s"',
            'endTime'   => 'nullable|date_format:"Y-m-d H:i:s"',
            'sorts'     => ['nullable', new SortArray(['id', 'type', 'createdAt'])],
        ]))->check();
    }

    public static function checkReportDepositWithdraw($data)
    {
        (new static($data, [
            'startTime' => 'nullable|date_format:"Y-m-d H:i:s"',
            'endTime'   => 'nullable|date_format:"Y-m-d H:i:s"',
            'sorts'     => ['nullable', new SortArray(['type', 'createdAt'])],
        ]))->check();
    }

    public static function checkTransferLogs($data)
    {
        (new static($data, [
            'memberId'   => 'required|exists:member,id',
            'platformId' => 'nullable|exists:game_platform,id',
            'startTime'  => 'nullable|date_format:Y-m-d H:i:s',
            'endTime'    => 'nullable|date_format:Y-m-d H:i:s',
            'sorts'      => ['nullable', new SortArray(['id', 'type', 'createdAt'])],
        ]))->check();
    }

    public static function checkplatformToggleEnable($data)
    {
        (new static($data, [
            'id'         => 'nullable|exists:member_platform_active,id',
            'memberId'   => 'required|exists:member,id',
            'platformId' => 'required|exists:game_platform,id',
            'enabled'    => 'required|in:0,1',
        ]))->check();
    }

    public static function checkCouponRedeemList($data)
    {
        (new static($data, [
            'memberId'   => 'required|exists:member,id',
            'type'       => 'required|array',
            'platformId' => 'numeric|exists:game_platform,id',
        ]))->check();
    }

    public static function checkGiveOrTakeBackAmount($data)
    {
        (new static($data, [
            'id'                 => 'required|exists:member,id',
            'amount'             => 'required|numeric|min:0.0001',
            'betAmount'          => 'nullable|numeric',
            'deductionBetAmount' => 'nullable|numeric',
            'remark'             => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkGiveOrTakeBackReward($data)
    {
        (new static($data, [
            'id'     => 'required|exists:member,id',
            'amount' => 'required|numeric',
            'remark' => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkAddDeposit($data)
    {
        (new static($data, [
            'id'     => 'required|exists:member,id',
            'amount' => 'required|numeric',
            'remark' => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkResetLockMoneyLog($data)
    {
        (new static($data, [
            'memberId' => 'required|exists:member,id',
            'sorts'    => ['nullable', new SortArray(['type', 'createdAt'])],
            'page'     => 'nullable|integer|min:1',
            'perPage'  => 'nullable|integer|min:1',
        ]))->check();
    }

    public static function checkAddSummary($data)
    {
        (new static($data, [
            'name'     => 'required|string',
            'memberId' => 'required|exists:member,id',
            'summary'  => ['required', 'string'],
        ]))->check();
    }

    public static function checkBankGroupUpdate($data)
    {
        (new static($data, [
            'group_id' => 'required|integer|exists:bank_group,id',
        ]))->check();
    }

    public static function checkRigorousSearch($data)
    {
        (new static($data, [
            'account' => ['required', new StringRegex(StringRegex::TYPE_MEMBER_ACCOUNT)],
        ]))->check();
    }

    public static function checkForthGenerationOrganizationDetail($data)
    {
        (new static($data, [
            'level'    => 'required|in:1,2,3',
        ]))->check();
    }


    public static function checkLostMoneyFromPlatform($data)
    {
        (new static($data, [
            'id'         => 'required|integer',
            'amount'     => 'required|numeric|min:0',
            'platformId' => 'required|integer',
            'remark'     => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkForceCancelReward($data)
    {
        (new static($data, [
            'memberId' => 'required|integer',
            'detailId' => 'required|integer',
        ]))->check();
    }
}
