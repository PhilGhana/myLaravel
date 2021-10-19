<?php

namespace App\Validators\Agent;

use App\Models\Agent;
use App\Rules\StringRegex;
use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\AllowFranchisee;
use App\Validators\ExpansionRules\SortArray;

class AgentValidator extends BaseValidator
{
    /**
     * 檢查 Agent 的新增資料是否正確.
     *
     * @param array $data
     * @return void
     */
    public static function checkAgentAdd($data)
    {
        $parentId = $data['parentId'] ?? 0;
        $extendId = $data['extendId'] ?? 0;

        (new static($data, [
            'franchiseeId' => [new AllowFranchisee()],
            'account'      => ['required', 'string', new StringRegex(StringRegex::TYPE_MEMBER_ACCOUNT)],
            'password'     => ['required', 'string', 'max:40', new StringRegex],
            'name'         => ['required', 'string', 'max:20', new StringRegex],
            'roleId'       => 'required|exists:role,id',
            'parentId'     => ($parentId ? 'exists:agent,id' : 'nullable|in:0'),
            'extendId'     => ($extendId ? 'exists:agent,id' : 'nullable|in:0'),
            'enabled'      => 'in:0,1',
            'locked'       => 'in:0,1',
            'site'         => ['nullable', 'string', 'max:256', new StringRegex(StringRegex::TYPE_LINK)],
        ]))->check();
    }

    public static function checkEditAgent($data)
    {
        $agent         = Agent::find($data['id'] ?? 0);
        $maxFeePercent = $agent ? $agent->maxFeePercent() : 0;
        (new static($data, [
            'id'         => 'required|exists:agent,id',
            'name'       => ['required', 'string', 'max:20', new StringRegex],
            'roleId'     => 'required|exists:role,id',
            'enabled'    => 'required|in:0,1',
            'locked'     => 'required|in:0,1',
            'feePercent' => "nullable|numeric|min:0|max:{$maxFeePercent}",
            'site'       => ['nullable', 'string', 'max:256', new StringRegex(StringRegex::TYPE_LINK)],
        ]))->check();
    }

    public static function checkEditPassword($data)
    {
        (new static($data, [
            'id'       => 'required|exists:agent,id',
            'password' => ['required', 'string', 'max:40', new StringRegex],
        ]))->check();
    }

    public static function checkSavePlatformConfig($data)
    {
        (new static($data, [
            'id' => 'required|exists:agent,id',
        ]))->check();
    }

    public static function checkEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:agent,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkLocked($data)
    {
        (new static($data, [
            'id'     => 'required|exists:agent,id',
            'locked' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkGetAgList($data)
    {
        (new static($data, [
            'franchiseeId' => 'nullable|integer',
            'level'        => 'required|numeric|in:0,1,2,3,4,5',
            'parentId'     => 'nullable|numeric|exists:agent,id',
            'name'         => 'nullable|string|max:20',
            'account'      => 'nullable|string|max:20',
            'enabled'      => 'numeric|in:-1,0,1',
            'sort'         => ['nullable', new SortArray(['id', 'name', 'key'])],
            'locked'       => 'numeric|in:-1,0,1',
            'page'         => 'numeric',
            'perPage'      => 'numeric',
        ]))->check();
    }

    public static function checkGetRoles($data)
    {
        (new static($data, [
            'level'        => 'required|numeric|in:0,1,2,3,4,5',
        ]))->check();
    }

    public static function checkGetSubList($data)
    {
        (new static($data, [
            'parentId' => 'nullable|exists:agent,id',
            'account'  => 'nullable|string|max:20',
            'enabled'  => 'nullable|in:-1,0,1',
            'locked'   => 'nullable|in:-1,0,1',
            'sorts'    => ['nullable', new SortArray(['id', 'name', 'account'])],
            'page'     => 'numeric|min:1',
            'perPage'  => 'numeric|min:1',
        ]))->check();
    }

    public static function chechGetWalletLogList($data)
    {
        (new static($data, [
            'id'        => 'required|exists:agent,id',
            // 'type' => 'nullable|in:all,settlement,withdraw,withdraw-reject,edit-settlement,deposit-bank,deposit-third,edit-money,money-to-settlement,settlement-to-money,transfer-to-agent,transfer-from-agent,give-money,take-back',
            'type'      => 'string|in:all,settlement,withdraw,deposit,edit,transfer-member,transfer-agent,redeem',
            'startTime' => 'nullable|date_format:Y-m-d H:i:s',
            'endTime'   => 'nullable|date_format:Y-m-d H:i:s',
            'page'      => 'nullable|integer|min:1',
            'perPage'   => 'nullable|integer|min:1',
        ]))->check();
    }

    public static function chechIpWhitelistAdd($data)
    {
        (new static($data, [
            'id' => 'required|exists:agent,id',
            'ip' => ['required', 'string', 'max:100', 'ip'],
        ]))->check();
    }

    public static function checkIpWhitelistRemove($data)
    {
        (new static($data, [
            'id' => 'required|exists:agent_ip_whitelist,id',
        ]))->check();
    }

    public static function checkIpWhitelistAll($data)
    {
        (new static($data, [
            'id' => 'required|exists:agent,id',
        ]))->check();
    }

    public static function checkEditWalletMoney($data)
    {
        (new static($data, [
            'id'     => 'required|exists:agent,id',
            'amount' => 'required|numeric',
            'remark' => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkEditWalletSettlement($data)
    {
        (new static($data, [
            'id'     => 'required|exists:agent,id',
            'amount' => 'required|numeric',
            'remark' => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkGiveOrTakeBackAmount($data)
    {
        (new static($data, [
            'id'     => 'required|exists:agent,id',
            'amount' => 'required|numeric|min:1',
            'remark' => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkLoan($data)
    {
        (new static($data, [
            'id'     => 'required|exists:agent,id',
            'amount' => 'required|numeric|min:1',
            'remark' => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkWriteOff($data)
    {
        (new static($data, [
            'id'     => 'required|exists:agent,id',
            'amount' => 'required|numeric|min:1',
            'remark' => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkMoveInviteLog($data)
    {
        (new static($data, [
            'id'      => 'required|exists:agent,id',
            'page'    => 'numeric|min:1',
            'perPage' => 'numeric|min:1',
        ]))->check();
    }

    public static function checkModifyInviteLog($data)
    {
        (new static($data, [
            'from' => 'required|exists:agent,id',
            'to'   => 'required|exists:agent,id',
        ]))->check();
    }

    public static function fetchInviteInfo($data)
    {
        (new static($data, [
            'id' => 'required|exists:agent,id',
        ]))->check();
    }
}