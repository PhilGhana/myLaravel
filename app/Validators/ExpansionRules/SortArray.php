<?php
namespace App\Validators\ExpansionRules;

use Illuminate\Contracts\Validation\Rule;


/**
 * 檢查排序參數是否正確
 * 使用方法：
 *
 * # 必填, 且只能排序 id, name, key
 * sort => ['required', new SortArray(['id', 'name', 'key'])]
 */
class SortArray implements Rule
{
    protected $sorts;
    public function __construct($sorts)
    {
        $this->sorts = $sorts;
    }


    public function passes($attribute, $value)
    {
        # 是陣列才檢查
        if ($value && is_array($value)) {
            $sorts = $this->sorts;
            $types = ['asc', 'desc'];
            $integers = array_filter($value, function ($v) use ($types, $sorts) {
                list($col, $by) = explode(',', $v . ',');
                return !in_array($col, $sorts) || !in_array(strtolower($by), $types);
            });
            return count($integers) === 0;
        }
        return true;
    }

    public function message()
    {
        return ':attribute not array or value error';
    }

}
