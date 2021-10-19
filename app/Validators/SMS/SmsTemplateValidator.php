<?php

namespace App\Validators\SMS;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;

class SmsTemplateValidator extends BaseValidator
{
    private static $smsData = [
        'smsUserId' => 'required|exists:sms_user,id',
        'name'      => [
            'required',
            'string',
            'max:20',
        ],
        'content'   => [
            'required',
            'string',
        ],
        'enabled'   => 'required|in:0,1',
        'remark'    => [
            'nullable',
            'string',
        ],
    ];

    public static function checkAdd($data)
    {
        $checkData = [
            'smsUserId' => 'required|exists:sms_user,id',
            'name'      => [
                'required',
                'string',
                'max:20',
                new StringRegex,
            ],
            'content'   => [
                'required',
                'string',
            ],
            'enabled'   => 'required|in:0,1',
            'remark'    => [
                'nullable',
                'string',
            ],
        ];

        (new static($data, $checkData))->check();
    }

    public static function checkEdit($data)
    {
        $checkData = [
            'smsUserId' => 'required|exists:sms_user,id',
            'name'      => [
                'required',
                'string',
                'max:20',
                new StringRegex,
            ],
            'content'   => [
                'required',
                'string',
            ],
            'enabled'   => 'required|in:0,1',
            'remark'    => [
                'nullable',
                'string',
            ],
            'id'        => 'required|exists:sms_template,id',
        ];

        (new static($data, $checkData))->check();
    }

    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:sms_template,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkGetList($data)
    {
        (new static($data, [
            'enabled' => 'numeric|in:-1,0,1',
            'page'    => 'numeric',
            'perPage' => 'numeric',
        ]))->check();
    }

}