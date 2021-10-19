<?php
namespace App\Validators\AgentTrade;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;

class AgentTradeValidator extends BaseValidator
{
    public static function transferAgent($data)
    {
        (new static($data, [
            'agentId' => 'required|exists:agent,id',
            'money'   => 'required|numeric|min:0',
            'remark'  => ['nullable', 'string'],
        ]))->check();
    }

    public static function giveMoney($data)
    {
        (new static($data, [
            'memberId' => 'required|exists:member,id',
            'money'    => 'required|numeric|min:0',
            'remark'   => ['nullable', 'string'],
        ]))->check();
    }

    public static function takeBack($data)
    {
        (new static($data, [
            'memberId' => 'required|exists:member,id',
            'money'    => 'required|numeric|min:0',
            'remark'   => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkOptions($data)
    {
        (new static($data, [
            'account' => ['nullable', 'string'],
        ]))->check();
    }

    public static function checkSettlement($data)
    {
        (new static($data, [
            'money'  => ['nullable', 'numeric', 'min:0'],
            'remark' => ['nullable', 'string'],
        ]))->check();
    }
}
