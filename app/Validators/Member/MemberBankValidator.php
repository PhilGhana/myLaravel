<?php

namespace App\Validators\Member;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;
use Illuminate\Validation\Rule;

class MemberBankValidator extends BaseValidator
{
    public static function save($data)
    {
        (new static($data, [
            'memberId' => 'required|exists:member,id',
            'account'  => [
                'required',
                'numeric',
            ],
            'name'     => [
                'required',
                'string',
                new StringRegex,
            ],
            'bankName' => [
                'required',
                'string',
                new StringRegex,
            ],
            'branchName' => [
                'nullable',
                'string',
                new StringRegex,
            ],
            'bankCode' => [
                'nullable',
                'string',
                new StringRegex,
            ],
        ]))->check();
    }
    public static function toggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:member_bank,id',
            'enabled' => 'required|in:0,1',
        ]))->check();

    }
}
