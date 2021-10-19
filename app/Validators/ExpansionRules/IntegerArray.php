<?php
namespace App\Validators\ExpansionRules;
use Illuminate\Contracts\Validation\Rule;

/**
 * 檢查是否為整數陣列
 */
class IntegerArray implements Rule
{
    public function passes($attribute, $value)
    {
        if ($value) {

            if (is_array($value)) {

                $integers = array_filter($value, function($v) {
                    return is_numeric($v) && (floor($v) === (real) $v);
                });

                return count($value) === count($integers);
            }

            return false;
        }

        return true;
    }

    public function message ()
    {
        return ':attribute not array or type error';
    }

}
