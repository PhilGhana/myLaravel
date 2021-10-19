<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class StringRegex implements Rule
{
    const TYPE_GAME_PLATFORM_NAME   = 'game-platform-name';
    const TYPE_MEMBER_ACCOUNT       = 'member-account';
    const TYPE_CLASS_NAME           = 'class-name';
    const TYPE_NUMBER_ONLY          = 'number';
    const TYPE_CHAR_NUMBER_ONLY     = 'char-number';
    const TYPE_GAME_PLATFORM_PREFIX = 'game-platform-prefix';
    const TYPE_LINK                 = 'link';

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($type = '')
    {
        $this->type = $type;
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
        $result = 0;
        switch ($this->type) {
            case static::TYPE_GAME_PLATFORM_NAME:
                $result = preg_match_all('/^[\x{4e00}-\x{9fa5}0-9a-zA-Z_ ]+$/u', $value);
                break;

            case static::TYPE_MEMBER_ACCOUNT:
                // 和 MEMBER 一致，至少包含一個英文字母
                $result     = preg_match_all('/^[0-9a-zA-Z]*[a-zA-Z_]+[0-9a-zA-Z_]*$/', $value, $checks);
                break;

            case static::TYPE_CLASS_NAME:
                $result = preg_match_all('/^[0-9a-zA-Z-]+$/', $value);
                break;

            case static::TYPE_NUMBER_ONLY:
                $result = preg_match_all('/^[0-9]+$/', $value);
                break;

            case static::TYPE_CHAR_NUMBER_ONLY:
                $result = preg_match_all('/^[0-9a-zA-Z-]+$/', $value);
                break;

            // 只可以英數和底線
            case static::TYPE_GAME_PLATFORM_PREFIX:
                $result = preg_match_all('/^[0-9a-zA-Z_-]+$/', $value);
                break;
            // 網址 英數和/?&_:
            case static::TYPE_LINK:
                $result = preg_match_all('/^[0-9a-zA-Z\/?&_:.-]+$/', $value);
                break;

            default:
                $result = preg_match_all('/^[\x{4e00}-\x{9fa5}0-9a-zA-Z_-]+$/u', $value);
                break;
        }

        return $result === 1;
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