<?php
namespace App\Validators\Review;

use App\Rules\StringRegex;
use App\Rules\StringSymbolRegex;

class ReviewAgentCouponValidator extends ReviewBaseValidator
{
    public static function checkApprove($data)
    {
        (new static($data, [
            'id'     => 'required|exists:review_agent_coupon,id',
            'remark' => ['nullable', 'string', 'max:50'],
        ]))->check();
    }

    public static function checkDisapprove($data)
    {
        (new static($data, [
            'id'     => 'required|exists:review_agent_coupon,id',
            'reason' => ['required', 'string', 'max:50'],
            'remark' => ['nullable', 'string', 'max:50'],
        ]))->check();
    }
}
