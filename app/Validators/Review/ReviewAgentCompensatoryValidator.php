<?php
namespace App\Validators\Review;

use App\Rules\StringRegex;
use App\Rules\StringSymbolRegex;

class ReviewAgentCompensatoryValidator extends ReviewBaseValidator
{
    public static function checkApprove($data)
    {
        (new static($data, [
            'id'                 => 'required|exists:review_agent_compensatory,id',
            'remark'             => ['nullable', 'string', 'max:50'],
            'showBetAmountInput' => 'nullable|min:0',
        ]))->check();
    }

    public static function checkDisapprove($data)
    {
        (new static($data, [
            'id'     => 'required|exists:review_agent_compensatory,id',
            'reason' => ['required', 'string', 'max:50'],
            'remark' => ['nullable', 'string', 'max:50'],
        ]))->check();
    }
}
