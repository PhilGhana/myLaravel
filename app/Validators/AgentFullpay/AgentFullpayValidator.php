<?php

namespace App\Validators\AgentFullpay;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;

class AgentFullpayValidator extends BaseValidator
{
    public static function checkAgent($data)
    {
        (new static($data, [
            'id'     => 'required|exists:agent,id',
        ]))->check();
    }

    public static function checkMember($data)
    {
        (new static($data, [
            'id'     => 'required|exists:member,id',
        ]))->check();
    }

    public static function checkModify($data)
    {
        (new static($data, [
            'id'                           => 'required|exists:agent,id',
            'bank_group_id'                => 'required|integer|min:0',
        ]))->check();
    }

    public static function checkModifyMember($data)
    {
        (new static($data, [
            'id'                           => 'required|exists:member,id',
            'bank_group_id'                => 'required|integer|min:0',
        ]))->check();
    }
}
