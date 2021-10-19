<?php
namespace App\Validators\Review;

class ReviewNotifyValidator extends ReviewBaseValidator
{

    public static function checkAdd($data)
    {
        (new static($data, [
            'key'         => 'required|string|exists:review_type,key',
            'status'      => 'required|string|in:approved,disapproved,transaction,transaction-cancel',
            'letterTagId' => 'required_if:enabled,1|numeric',
            'smsUserId'   => 'required_if:enabled,1|numeric',
            'emailUserId' => 'required_if:enabled,1|numeric',
            'title'       => 'required_if:enabled,1|nullable|string|max:40',
            'content'     => 'required_if:enabled,1|nullable|string',
            'enabled'     => 'required|in:0,1',
        ]))->check();
    }

    public static function checkEdit($data)
    {
        (new static($data, [
            'id'          => 'required|exists:review_notify,id',
            'key'         => 'required|string|exists:review_type,key',
            'status'      => 'required|string|in:approved,disapproved,transaction,transaction-cancel',
            'letterTagId' => 'required_if:enabled,1|numeric',
            'smsUserId'   => 'required_if:enabled,1|numeric',
            'emailUserId' => 'required_if:enabled,1|numeric',
            'title'       => 'required_if:enabled,1|nullable|string|max:40',
            'content'     => 'required_if:enabled,1|nullable|string',
            'enabled'     => 'required|in:0,1',
        ]))->check();
    }

    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:review_notify,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkAll($data)
    {
        (new static($data, [
            'key' => 'required|string|exists:review_type,key',
        ]))->check();
    }

}