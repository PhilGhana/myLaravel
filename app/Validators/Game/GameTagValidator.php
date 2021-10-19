<?php
namespace App\Validators\Game;

use App\Rules\TagRegex;
use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\IntegerArray;
use Illuminate\Validation\Rule;

class GameTagValidator extends BaseValidator
{
    /**
     * 檢查 game-type 的儲存資料是否正確
     *
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkAdd($data)
    {
        (new static($data, [
            'tag'  => 'required|string|max:20|unique:game_tag',
            'name' => ['required', 'string', 'max:20', new TagRegex],
        ]))->check();
    }

    public static function checkEdit($data)
    {
        (new static($data, [
            'tag'  => 'required|string|max:10|exists:game_tag',
            'name' => ['required', 'string', 'max:20', new TagRegex],
        ]))->check();
    }
}
