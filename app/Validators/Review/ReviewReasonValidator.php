<?php

namespace App\Validators\Review;

use App\Rules\StringRegex;
use App\Rules\StringSymbolRegex;

class ReviewReasonValidator extends ReviewBaseValidator
{
    public static function addCheck($data)
    {
        (new static($data, [
            'franchiseeId'  => 'required|exists:franchisee,id',
            'reviewTypeKey' => [
                'required',
                'string',
                'max:50',
                'exists:review_type,key',
                new StringRegex(StringRegex::TYPE_CHAR_NUMBER_ONLY),
            ],
            'reason'        => [
                'required',
                'string',
                'max:180',
            ],
        ]))->check();
    }

    public static function editCheck($data)
    {
        (new static($data, [
            'id'     => 'required|exists:review_reason,id',
            'reason' => [
                'required',
                'string',
                'max:180',
            ],
        ]))->check();
    }

    public static function removeCheck($data)
    {
        (new static($data, [
            'id' => 'required|exists:review_reason,id',
        ]))->check();
    }

    public static function listCheck($data)
    {
        (new static($data, [
            'franchiseeId'  => 'required',
            'reason'        => [
                'nullable',
                'string',
            ],
            'reviewTypeKey' => [
                'nullable',
                'max:50',
                new StringRegex(StringRegex::TYPE_CHAR_NUMBER_ONLY),
            ],
        ]))->check();
    }
}
