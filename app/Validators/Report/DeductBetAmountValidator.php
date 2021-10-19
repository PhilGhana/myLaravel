<?php
namespace App\Validators\Report;

use App\Validators\BaseValidator;

class DeductBetAmountValidator extends BaseValidator
{
    public static function deductBetAmount($data)
    {
        (new static($data, [
            'memberId' => 'required|integer|exists:member,id',
            'reportId' => 'required|integer|exists:report,id',
        ]))->check();
    }
}
