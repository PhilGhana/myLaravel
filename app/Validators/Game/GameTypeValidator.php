<?php

namespace App\Validators\Game;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\ArrayExists;
use App\Validators\ExpansionRules\IntegerArray;
use Illuminate\Validation\Rule;

class GameTypeValidator extends BaseValidator
{
    /**
     * 檢查 game-type 的儲存資料是否正確.
     *
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkAdd($data)
    {
        (new static($data, [
            'type' => ['required', 'string', 'max:10', 'unique:game_type', new StringRegex(StringRegex::TYPE_CHAR_NUMBER_ONLY)],
            'name' => ['required', 'string', 'max:20', new StringRegex],
        ]))->check();
    }

    public static function checkEdit($data)
    {
        (new static($data, [
            'type' => ['required', 'string', 'max:10', 'exists:game_type', new StringRegex(StringRegex::TYPE_CHAR_NUMBER_ONLY)],
            'name' => ['required', 'string', 'max:20', new StringRegex],
        ]))->check();
    }
}
