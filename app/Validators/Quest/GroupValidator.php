<?php
namespace App\Validators\Quest;

use App\Validators\BaseValidator;

use App\Validators\ExpansionRules\IntegerArray;

use Illuminate\Validation\Rule;
use App\Validators\ExpansionRules\ArrayExists;

class GroupValidator extends BaseValidator
{
    /**
     *
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkAdd($data)
    {
        (new static($data, [
            'franchiseeId' => 'required|numeric',
            'name' => 'required|string',
            'order' => 'required|numeric',
        ]))->check();
    }

    public static function checkEdit($data)
    {
        (new static($data, [
            'order' => 'required|numeric',
            'name' => 'required|string',
        ]))->check();
    }
}
