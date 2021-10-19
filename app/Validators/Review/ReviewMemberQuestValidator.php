<?php

namespace App\Validators\Review;

use App\Rules\StringRegex;
use App\Rules\StringSymbolRegex;

class ReviewMemberQuestValidator extends ReviewBaseValidator
{
    public static function checkApprove($data)
    {
        (new static($data, [
            'id'     => 'required|exists:review_member_quest,id',
            'remark' => [
                'nullable',
                'string',
                'max:50',
            ],
        ]))->check();
    }

    public static function checkDisapprove($data)
    {
        (new static($data, [
            'id'     => 'required|exists:review_member_quest,id',
            'reason' => [
                'required',
                'string',
                'max:50',
            ],
            'remark' => [
                'nullable',
                'string',
                'max:50',
            ],
        ]))->check();
    }
}
