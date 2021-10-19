<?php

namespace App\Validators\Letter;

use App\Rules\TagRegex;
use App\Validators\BaseValidator;

class LetterTagValidator extends BaseValidator
{
    private static $letterTagData = [
        'type'         => 'required|in:announcement,system,agent',
        'name'         => 'required|string|max:10',
        'franchiseeId' => 'required',
        'enabled'      => 'required|in:0,1',
    ];

    public static function checkAddLetterTag($data)
    {
        $checkData = [
            'type'         => 'required|in:announcement,system,agent',
            'name'         => ['required', 'string', 'max:10', new TagRegex],
            'franchiseeId' => 'required|integer',
            'enabled'      => 'required|in:0,1',
        ];

        (new static($data, $checkData))->check();
    }

    public static function checkEditLetterTag($data)
    {
        $checkData = [
            'id'           => 'required|exists:letter_tag,id',
            'name'         => ['required', 'string', 'max:10', new TagRegex],
            'franchiseeId' => 'required|integer',
            'enabled'      => 'required|in:0,1',
        ];

        (new static($data, $checkData))->check();
    }

    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:letter_tag,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkListTag($data)
    {
        (new static($data, [
            'type'    => 'string|in:all,announcement,system,agent',
            'enabled' => 'in:-1,0,1',
            'perPage' => 'numeric',
        ]))->check();
    }
}
