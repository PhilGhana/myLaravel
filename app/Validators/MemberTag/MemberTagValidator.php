<?php
namespace App\Validators\MemberTag;

use App\Rules\StringRegex;
use App\Rules\TagRegex;
use App\Validators\BaseValidator;

class MemberTagValidator extends BaseValidator
{
    public static function checkAddTag($data)
    {
        (new static($data, [
            'name'         => ['required', 'string', 'max:20'],
            'franchiseeId' => 'required|exists:franchisee,id',
            'color'        => 'regex:/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/',
            'remark'       => ['string', 'max:50', 'nullable'],
        ]))->check();
    }

    public static function checkEditTag($data)
    {
        (new static($data, [
            'id'     => 'required|exists:member_tag,id',
            'name'   => ['required', 'string', 'max:20'],
            'color'  => 'regex:/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/',
            'remark' => ['string', 'max:50', 'nullable'],
        ]))->check();
    }

    public static function checkGetList($data)
    {
        (new static($data, [
            'page'    => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]))->check();
    }
}
