<?php

namespace App\Validators\Bank;

use App\Exceptions\FailException;
use App\Rules\StringRegex;
use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\IntegerArray;

class BankGroupValidator extends BaseValidator
{
    /**
     * 檢查 Club 的新增資料是否正確.
     *
     * @param array $data
     * @return void
     */
    public static function checkAdd($data)
    {
        (new static($data, [
            'name'         => ['required', 'string', 'max:20', new StringRegex],
            'enabled'      => 'required|in:0,1',
            'describe'     => ['required', 'string', 'max:50', new StringRegex],
            'fullpay'      => ['nullable', 'string', 'max:50', new StringRegex(StringRegex::TYPE_CHAR_NUMBER_ONLY)],
            'franchiseeId' => 'required|exists:franchisee,id',
        ]))->check();
    }

    /**
     * 檢查 Club 的修改資料是否正確.
     *
     * @param array $data
     * @return void
     */
    public static function checkEdit($data)
    {
        (new static($data, [
            'name'         => ['required', 'string', 'max:20', new StringRegex],
            'enabled'      => 'required|in:0,1',
            'describe'     => ['required', 'string', 'max:50', new StringRegex],
            'fullpay'      => ['nullable', 'string', 'max:50', new StringRegex(StringRegex::TYPE_CHAR_NUMBER_ONLY)],
            'franchiseeId' => 'required|exists:franchisee,id',
            'id'           => 'required|integer|exists:bank_group,id',
        ]))->check();
    }

    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|integer|exists:bank_group,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkConfigEdit($data)
    {
        (new static($data, [
            'id'          => 'required|integer',
            'order'       => 'required|integer',
            'amountLimit' => 'required|integer',
        ]))->check();
    }

    public static function checkFullpayOptions($data)
    {
        (new static($data, [
            'franchiseeId' => 'required|integer',
        ]))->check();
    }

    public static function checkCurentAmountModify($data)
    {
        (new static($data, [
            'id'            => 'required|integer|exists:bank_group_fullpay_configs,id',
            'amountCurrent' => 'required|integer',
        ]))->check();
    }
}
