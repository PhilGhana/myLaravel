<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class QuestNameRegex implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        preg_match_all('/[^\x7f-\xff\d\w\_\s\(\-\!\%\+\:\.\)]+/', $value, $matches);

        return count($matches[0]) === 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.regex');
    }
}
