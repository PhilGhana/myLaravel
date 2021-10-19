<?php
namespace App\Validators\Review;

use App\Rules\StringRegex;
use App\Rules\StringSymbolRegex;

class ReviewAgentEditDepositValidator extends ReviewBaseValidator
{
    public static function checkApprove($data)
    {
        (new static($data, [
            'id'     => 'required|exists:review_agent_edit_deposit,id',
            'remark' => ['nullable', 'string', 'max:50'],
        ]))->check();
    }

    public static function checkDisapprove($data)
    {
        (new static($data, [
            'id'     => 'required|exists:review_agent_edit_deposit,id',
            'reason' => ['required', 'string', 'max:50'],
            'remark' => ['nullable', 'string', 'max:50'],
        ]))->check();
    }

    public static function checkGetList($data)
    {
        (new static($data, [
            'status'             => 'nullable|in:all,review,approved,disapproved,cancel',
            'transactionAtStart' => 'nullable|date_format:Y-m-d H:i:s',
            'transactionAtEnd'   => 'nullable|date_format:Y-m-d H:i:s',
            'page'               => 'nullable|integer|min:1',
            'perPage'            => 'nullable|integer|min:1',
        ]))->check();
    }

    public static function rollback($data)
    {
        (new static($data, [
            'id' => 'required|exists:review_agent_edit_deposit,id',
        ]))->check();
    }
}
