<?php

namespace App\Validators\Report;

use App\Validators\BaseValidator;

class AccountReportValidator extends BaseValidator
{

    public static function checkBankList($data)
    {
        (new static($data, [
            'franchiseeId' => 'required|integer',
        ]))->check();
    }

    public static function checkAccountReport($data)
    {
        (new static($data, [
            'franchiseeId' => 'required|integer',
            'type' => 'required|string',
        ]))->check();
    }
}
