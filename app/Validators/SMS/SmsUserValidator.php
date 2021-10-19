<?php

namespace App\Validators\SMS;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;

class SmsUserValidator extends BaseValidator
{
    private static $smsData = [
        'franchiseeId' => 'required|exists:franchisee,id',
        'moduleId'     => 'required|exists:sms_module,id',
        'username'     => [
            'required',
            'string',
            'max:50',
        ],
        'password'     => [
            'required',
            'string',
            'max:50',
        ],
        'signature'    => [
            'nullable',
            'string',
            'max:200',
        ],
    ];

    public static function checkAdd($data)
    {
        $checkData = [
            'franchiseeId' => 'required|exists:franchisee,id',
            'moduleId'     => 'required|exists:sms_module,id',
            'username'     => [
                'required',
                'string',
                'max:50',
                new StringRegex,
            ],
            'password'     => [
                'required',
                'string',
                'max:50',
                new StringRegex,
            ],
            'signature'    => [
                'nullable',
                'string',
                'max:200',
            ],
        ];

        (new static($data, $checkData))->check();
    }

    public static function checkEdit($data)
    {
        $checkData = [
            'franchiseeId' => 'required|exists:franchisee,id',
            'moduleId'     => 'required|exists:sms_module,id',
            'username'     => [
                'required',
                'string',
                'max:50',
                new StringRegex,
            ],
            'password'     => [
                'required',
                'string',
                'max:50',
                new StringRegex,
            ],
            'signature'    => [
                'nullable',
                'string',
                'max:200',
            ],
            'id'           => 'required|exists:sms_user,id',
        ];

        (new static($data, $checkData))->check();
    }

    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:sms_user,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkGetList($data)
    {
        (new static($data, [
            'moduleId' => 'nullable',
            'enabled'  => 'nullable|in:-1,0,1',
            'page'     => 'nullable|integer|min:0',
            'perPage'  => 'nullable|integer|min:0',
        ]))->check();
    }
}
