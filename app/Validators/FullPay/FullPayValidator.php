<?php
namespace App\Validators\FullPay;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;

class FullPayValidator extends BaseValidator
{
    public static function list($data) {
        (new static($data, [
            'pay_id' => ['string', 'max:20', new StringRegex],
        ]))->check();
    }

    public static function manual($data) {
        (new static($data, [
            'id'    => 'required|integer',
        ]))->check();
    }
}