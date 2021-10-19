<?php

namespace App\Validators\AgentInfo;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\SortArray;

class AgentInfoValidator extends BaseValidator
{
    public static function checkEditName($data)
    {
        (new static($data, [
            'name' => ['required', 'string', 'max:20', new StringRegex],
        ]))->check();
    }

    public static function checkEditPassword($data)
    {
        (new static($data, [
            'oldPassword' => ['required', 'string', 'max:40', new StringRegex],
            'password'    => ['required', 'string', 'max:40', new StringRegex],
        ]))->check();
    }

    public static function checkGetWalletList($data)
    {
        (new static($data, [
            'startTime' => 'nullable|date',
            'endTime'   => 'nullable|date',
            // 'type' => 'string|in:settlement,withdraw,company-add-settlement,deposit-bank,deposit-third,company-add-money,money-to-settlement,settlement-to-money,transfer-to-agent,transfer-from-agent,give-money,take-back,edit-money',
            'type'      => 'string|in:all,settlement,withdraw,deposit,edit,transfer-member,transfer-agent,redeem',
            'sorts'     => ['nullable', new SortArray(['id', 'createdAt', 'type'])],
            'page'      => 'numeric',
            'perPage'   => 'numeric',
        ]))->check();
    }

    public static function checkWaleltLoan($data)
    {
        (new static($data, [
            'amount' => 'required|numeric|min:1',
            'remark' => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkAddInvitationHost($data)
    {
        (new static($data, [
            'host'   => ['required', 'string'],
            'remark' => ['required', 'string'],
        ]))->check();
    }

    public static function checkEditInvitationHost($data)
    {
        (new static($data, [
            'id'     => 'required|exists:invitation_hosts,id',
            'host'   => ['required', 'string'],
            'remark' => ['nullable', 'string'],
        ]))->check();
    }
}
