<?php
namespace App\Validators\ExpansionRules;
use Illuminate\Contracts\Validation\Rule;
use DB;

/**
 * 檢查是否為整數陣列
 */
class AllowFranchisee implements Rule
{
    protected $query;

    protected $colName;


    public function passes($attribute, $value)
    {
        $user = user()->model();

        if ($user->isCompany() && empty($value)) {
            return false;
        }
        return empty($value)
            ? true
            : DB::table('franchisee')->where('id', $value)->count() > 0;
    }

    public function message ()
    {
        return __('custom-validation.allow-franchisee');
    }

}
