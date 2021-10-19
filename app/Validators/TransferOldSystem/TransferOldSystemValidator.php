<?php

namespace App\Validators\TransferOldSystem;

use App\Validators\BaseValidator;

class TransferOldSystemValidator extends BaseValidator
{
    public static function checkTransfer($data)
    {
        (new static($data, [
            'id'             => ['required', 'numeric', 'exists:member,id'],
        ]))->check();
    }
}
