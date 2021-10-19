<?php
namespace App\Validators\System;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;

class ApiValidator extends BaseValidator
{
    /**
     * 檢查新增 api 的資料是否正確
     *
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkAddApi($data)
    {
        (new static($data, [
            'viewId'  => 'required|exists:view,id',
            'path'    => 'required|string|max:100|not_regex:/(\/[^0-9a-zA-Z\-]+)+/',
            'method'  => 'required|in:POST,GET',
            'enabled' => 'required|in:0,1',
            'remark'  => ['nullable', 'string', 'max:50'],
        ]))->check();
    }

    /**
     * 檢查修改 api 的資料是否正確
     *
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkEditApi($data)
    {
        (new static($data, [
            'id'      => 'required|exists:api,id',
            'viewId'  => 'required|exists:view,id',
            'path'    => 'required|string|max:100|not_regex:/(\/[^0-9a-zA-Z\-]+)+/',
            'method'  => 'required|in:POST,GET',
            'enabled' => 'required|in:0,1',
            'remark'  => ['nullable', 'string', 'max:50'],
        ]))->check();
    }

    /**
     * 檢查切換 api 開關的資料是否正確
     *
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:api,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    /**
     * 檢查取得 Api 列表的資料是否正確
     *
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkGetApiList($data)
    {
        (new static($data, [
            'method'  => 'nullable|in:all,POST,GET',
            'enabled' => 'nullable|in:-1,0,1',
            'page'    => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]))->check();
    }

}
