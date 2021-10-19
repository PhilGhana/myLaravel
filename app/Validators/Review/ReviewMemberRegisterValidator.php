<?php

namespace App\Validators\Review;

use App\Rules\StringRegex;
use App\Rules\StringSymbolRegex;

class ReviewMemberRegisterValidator extends ReviewBaseValidator
{
    public static function checkApprove($data, $isLastStep)
    {
        $rules = [
            'id'     => 'required|exists:review_member_register,id',
            'remark' => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
        if ($isLastStep) {
            $rules['clubRankId'] = 'required|exists:club_rank,id';
        }
        (new static($data, $rules))->check();
    }

    public static function checkDisapprove($data)
    {
        (new static($data, [
            'id'     => 'required|exists:review_member_register,id',
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
