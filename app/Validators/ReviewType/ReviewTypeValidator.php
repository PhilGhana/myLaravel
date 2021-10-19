<?php
namespace App\Validators\ReviewType;

use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\IntegerArray;

class ReviewTypeValidator extends BaseValidator
{
    public static function  addSetting ($data) {
        $user = user()->model();
        $franchiseeRules = ['required'];

        if (!user()->model()->isCompany()) {
            $franchiseeRules[] = "in:{$user->franchisee_id}";
        }

        (new static($data, [
            'franchiseeId' => implode('|', $franchiseeRules),
            // 'steps' => ['required', new IntegerArray(), 'exists:role,id'],
        ]))->check();
    }

    public static function toggleAutoPass ($data) {

        (new static($data, [
            'id' => 'required|exists:review_type_setting,key',
            'autoPass' => 'required|in:0,1'
        ]))->check();
    }

    public static function checkUpdateSteps($data)
    {
        (new static($data, [
            'steps' => ['required', new IntegerArray(), 'exists:role,id'],
        ]))->check();
    }
    public static function checkToggleEnabled($data)
    {

        (new static($data, [
            'id' => 'required|exists:review_type_setting,key',
            'autoPass' => 'required|in:0,1'
        ]))->check();
    }

}
