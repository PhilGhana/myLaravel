<?php

namespace App\Validators\Company;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;

class CompanyValidator extends BaseValidator
{
    private static $companyData = [
        'name'    => 'required|string|max:20',
        'roleId'  => 'required|exists:role,id',
        'enabled' => 'required|in:0,1',
        'locked'  => 'required|in:0,1',
    ];

    public static function checkCompanyAdd($data)
    {
        $checkData = [
            'name'     => ['required', 'string', 'max:20', new StringRegex],
            'roleId'   => 'required|exists:role,id',
            'enabled'  => 'required|in:0,1',
            'locked'   => 'required|in:0,1',
            'account'  => ['required', 'string', 'max:20', new StringRegex(StringRegex::TYPE_MEMBER_ACCOUNT)],
            'password' => ['required', 'string', 'max:40', new StringRegex],
        ];

        (new static($data, $checkData))->check();
    }

    public static function checkEditCompany($data)
    {
        $checkData = [
            'name'    => ['required', 'string', 'max:20', new StringRegex],
            'roleId'  => 'required|exists:role,id',
            'enabled' => 'required|in:0,1',
            'locked'  => 'required|in:0,1',
            'id'      => 'required|exists:agent,id',
        ];

        (new static($data, $checkData))->check();
    }

    public static function checkEditPassword($data)
    {
        (new static($data, [
            'id'       => 'required|exists:agent,id',
            'password' => ['required', 'string', 'max:40', new StringRegex],
        ]))->check();
    }

    public static function checkEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:agent,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkLocked($data)
    {
        (new static($data, [
            'id'     => 'required|exists:agent,id',
            'locked' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkGetList($data)
    {
        (new static($data, [
            'name'    => 'nullable|string|max:20',
            'account' => 'nullable|string|max:20',
            'enabled' => 'nullable|in:-1,0,1',
            'locked'  => 'nullable|in:-1,0,1',
            'roleId'  => 'nullable|integer',
            'page'    => 'nullable|integer|min:0',
            'perPage' => 'nullable|integer|min:0',
        ]))->check();
    }
}
