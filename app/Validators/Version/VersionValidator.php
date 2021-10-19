<?php

namespace App\Validators\Version;

use App\Validators\BaseValidator;
use App\Rules\StringRegex;

class VersionValidator extends BaseValidator
{
    /**
     *
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkAdd($data)
    {
        (new static($data, [
            'version' => [
                'required',
                'string',
                'max:50',
                'regex: /^[\d\.]*$/'
            ],
            'tittle' => [
                'required',
                'string',
                'max:50',
                new StringRegex
            ],
            'information' => [
                'required',
                'string',
            ],
        ]))->check();
    }

    public static function checkEdit($data)
    {
        (new static($data, [
            'id' => [
                'required',
                'integer',
                'exists:version_info,id'
            ],
            'version' => [
                'required',
                'string',
                'max:50',
                'regex: /^[\d\.]*$/'
            ],
            'tittle' => [
                'required',
                'string',
                'max:50',
                new StringRegex
            ],
            'information' => [
                'required',
                'string',
            ],
        ]))->check();
    }
}
