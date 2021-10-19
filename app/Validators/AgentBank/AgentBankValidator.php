<?php

namespace App\Validators\AgentBank;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;

class AgentBankValidator extends BaseValidator
{
    private static $checkArr = [
        'name'         => ['required', 'string', 'max:20'],
        'phone'        => ['nullable', 'string', 'min:10', 'max:10'],
        'idCard'       => ['nullable', 'string', 'max:20'],
        'provinceName' => ['nullable', 'string', 'max:20'],
        'cityName'     => ['nullable', 'string', 'max:20'],
        'enabled'      => 'required|in:0,1',
    ];

    public static function checkAddBank($data)
    {
        (new static($data, [
            'name'         => ['required', 'string', 'max:20', new StringRegex],
            'phone'        => ['nullable', 'string', 'min:10', 'max:10', new StringRegex(StringRegex::TYPE_NUMBER_ONLY)],
            'idCard'       => ['nullable', 'string', 'max:20', new StringRegex(StringRegex::TYPE_CHAR_NUMBER_ONLY)],
            'provinceName' => ['nullable', 'string', 'max:20', new StringRegex],
            'cityName'     => ['nullable', 'string', 'max:20', new StringRegex],
            'enabled'      => 'required|in:0,1',
            'account'      => ['required', 'string', 'max:50', new StringRegex(StringRegex::TYPE_CHAR_NUMBER_ONLY)],
            'bankName'     => ['required', 'string', 'max:50', new StringRegex],
            'branchName'   => ['required', 'string', 'max:20', new StringRegex],
        ]))->check();
    }

    public static function checkEditBank($data)
    {
        (new static($data, [
            'name'         => ['required', 'string', 'max:20', new StringRegex],
            'phone'        => ['nullable', 'string', 'min:10', 'max:10', new StringRegex(StringRegex::TYPE_NUMBER_ONLY)],
            'idCard'       => ['nullable', 'string', 'max:20', new StringRegex(StringRegex::TYPE_CHAR_NUMBER_ONLY)],
            'provinceName' => ['nullable', 'string', 'max:20', new StringRegex],
            'cityName'     => ['nullable', 'string', 'max:20', new StringRegex],
            'enabled'      => 'required|in:0,1',
            'id'           => 'required|exists:agent_bank,id',
        ]))->check();
    }

    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:agent_bank,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }
}
