<?php

namespace App\Validators\Logger;

use App\Validators\BaseValidator;

class SyncReportValidator extends BaseValidator
{
    public static function syncTimeRange($data)
    {
        (new static($data, [
            'id'    => 'required|integer|min:1',
            'stime' => 'required|date',
            'etime' => 'required|date',
        ]))->check();
    }

    public static function checkEditPassword($data)
    {
        (new static($data, [
            'oldPassword' => 'required|string|max:40',
            'password'    => 'required|string|max:40',
        ]))->check();
    }
}
