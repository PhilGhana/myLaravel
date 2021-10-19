<?php

namespace App\Validators\ExpansionRules;

use Illuminate\Contracts\Validation\Rule;

/**
 * 檢查是否為字串陣列
 */
class StringArray implements Rule
{
    public function passes($attribute, $value)
    {
        if ($value) {

            if (is_array($value)) {

                $strings = array_filter($value, function ($v) {
                    return is_string($v);
                });

                return count($value) === count($strings);
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
