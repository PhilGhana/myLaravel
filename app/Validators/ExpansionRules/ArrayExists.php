<?php
namespace App\Validators\ExpansionRules;
use Illuminate\Contracts\Validation\Rule;
use DB;

/**
 * 檢查是否為整數陣列
 */
class ArrayExists implements Rule
{
    protected $query;

    protected $colName;

    public function __construct($table, $colName)
    {
        $this->colName = $colName;
        $this->query = DB::table($table);
    }

    public function passes($attribute, $value)
    {
        if ($value) {

            if (is_array($value)) {

                return count($value) === $this->query->whereIn($this->colName, $value)->count();
            }

            return false;
        }

        return true;
    }

    public function message ()
    {
        return ':attribute not array or type error';
    }

    public function where ($cb)
    {
        $cb($this->query);
        return $this;
    }

}
