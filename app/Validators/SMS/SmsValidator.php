<?php

namespace App\Validators\SMS;

use App\Validators\BaseValidator;

class SmsValidator extends BaseValidator
{
    public static function checkSendSms($data)
    {
        (new static($data, [
            'phone'     => 'required|string|min:10|max:10',
            'content'   => 'required|string',
            'smsUserId' => 'required|exists:sms_user,id',
        ]))->check();
    }

}