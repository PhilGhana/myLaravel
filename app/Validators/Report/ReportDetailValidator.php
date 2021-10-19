<?php
namespace App\Validators\Report;

use App\Validators\BaseValidator;

class ReportDetailValidator extends BaseValidator
{
    public static function getDetail($data)
    {
        (new static($data, [
            'id' => 'required|integer|min:1',
        ]))->check();
    }
}
