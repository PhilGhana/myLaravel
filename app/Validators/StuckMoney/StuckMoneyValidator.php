<?php

namespace App\Validators\StuckMoney;

use App\Models\StuckMoney;
use App\Rules\StringRegex;
use App\Validators\BaseValidator;
use Illuminate\Validation\Rule;

class StuckMoneyValidator extends BaseValidator
{
    public static function checkList($data)
    {
        (new static($data, [
            'member_account' => ['nullable', 'string', new StringRegex(StringRegex::TYPE_MEMBER_ACCOUNT)],
            'agent_account'  => ['nullable', 'string', new StringRegex(StringRegex::TYPE_MEMBER_ACCOUNT)],
            'status'         => [
                'nullable',
                'string',
                Rule::in([StuckMoney::STATUS_CANCEL, StuckMoney::STATUS_COMPLETED, StuckMoney::STATUS_PENDING]),
            ],
            'process'        => [
                'nullable',
                'string',
                Rule::in([StuckMoney::PROCESS_NONE, StuckMoney::PROCESS_PENDING, StuckMoney::PROCESS_SEND_BACK]),
            ],
            'platform_id'    => 'nullable|integer',
            'franchisee_id'  => 'nullable|integer',
            'page'           => 'nullable|integer',
            'perPage'        => 'nullable|integer',
        ]))->check();
    }

    public static function checkModify($data)
    {
        (new static($data, [
            'id'             => ['required', 'numeric', 'exists:stuck_money,id'],
            'point'          => ['nullable', 'numeric', 'min:0'],
            'status'         => [
                'required',
                'string',
                Rule::in([StuckMoney::STATUS_CANCEL, StuckMoney::STATUS_COMPLETED]),
            ],
            'process'        => [
                'required',
                'string',
                Rule::in([StuckMoney::PROCESS_NONE, StuckMoney::PROCESS_SEND_BACK]),
            ],
        ]))->check();
    }
}
