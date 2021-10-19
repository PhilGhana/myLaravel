<?php

namespace App\Validators\Letter;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\SortArray;

class LetterMessageValidator extends BaseValidator
{

    public static function checkSend($data)
    {
        (new static($data, [
            'memberId' => 'required|exists:member,id',
            'title'    => ['required', 'string', 'max:40', new StringRegex],
            'content'  => 'required|string',
        ]))->check();
    }

    public static function checkReply($data)
    {
        (new static($data, [
            'letterId' => 'required|numeric|exists:letter_message,id',
            'memberId' => 'required|exists:member,id',
            'content'  => 'required|string|max:50',
        ]))->check();
    }

    public static function checkList($data)
    {
        (new static($data, [
            'account'   => ['nullable', 'string', new StringRegex],
            'startTime' => 'nullable|date_format:"Y-m-d H:i:s"',
            'endTime'   => 'nullable|date_format:"Y-m-d H:i:s"',
            'read'      => 'in:-1,0,1',
            'sorts'     => ['nullable', new SortArray(['id', 'replyAt', 'createdAt'])],
            'perPage'   => 'numeric',
        ]))->check();
    }
}
