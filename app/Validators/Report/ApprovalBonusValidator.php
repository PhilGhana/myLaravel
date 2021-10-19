<?php

namespace App\Validators\Report;

use App\Validators\BaseValidator;

class ApprovalBonusValidator extends BaseValidator
{
    public static function index($data)
    {
        (new static($data, [
            'date' => 'required|date_format:Y-m-d',
        ]))->check();
    }

    public static function approval($data)
    {
        (new static($data, [
            'date'           => 'required|date_format:Y-m-d',
            'items.*.id'     => 'required|integer',
            'items.*.amount' => 'required|not_in:0',
        ]))->check();
    }

    public static function info($data)
    {
        (new static($data, [
            'date'      => 'required|date_format:Y-m-d',
            'memberId'  => 'required|integer',
        ]))->check();
    }
}
