<?php
namespace App\Validators\Announcement;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;

class AnnouncementValidator extends BaseValidator
{
    public static function checkAdd($data)
    {
        (new static($data, [
            'title'    => ['required', 'string', new StringRegex],
            'content'  => ['nullable', 'string'],
            'visable'  => 'required|boolean',
            'start_at' => 'nullable|date',
            'end_at'   => 'nullable|date',
        ]))->check();
    }

    public static function checkModify($data)
    {
        (new static($data, [
            'id'       => 'required|integer',
            'title'    => ['required', 'string', new StringRegex],
            'content'  => ['nullable', 'string'],
            'visable'  => 'required|boolean',
            'start_at' => 'nullable|date',
            'end_at'   => 'nullable|date',
        ]))->check();
    }

    public static function checkToggleVisable($data)
    {
        (new static($data, [
            'id'      => 'required|integer',
            'visable' => 'required|boolean',
        ]))->check();
    }
}
