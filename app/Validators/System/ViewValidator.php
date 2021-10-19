<?php

namespace App\Validators\System;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;

class ViewValidator extends BaseValidator
{
    /**
     * 檢查新增 View 頁面的資料是否正確.
     *
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkAddView($data)
    {
        $validatorArr = [
            'parentId' => 'nullable|integer',
            'name'     => [
                'required',
                'string',
                'max:20',
                new StringRegex,
            ],
            'type' => 'required|in:0,1',
            'path' => [
                'nullable',
                'string',
                'max:100',
            ],
            'className' => [
                'nullable',
                'string',
                'max:100',
                new StringRegex(StringRegex::TYPE_CLASS_NAME),
            ],
            'order'   => 'nullable|integer|min:0',
            'enabled' => 'required|in:0,1',
            'remark'  => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
        if (isset($data['parentId'])) {
            if ($data['parentId'] !== '0') {
                $validatorArr['parentId'] = 'required|exists:view,id';
            }
        }
        (new static($data, $validatorArr))->check();
    }

    /**
     * 檢查修改 View 頁面的資料是否正確.
     *
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkEditView($data)
    {
        $validatorArr = [
            'id'       => 'required|exists:view,id',
            'parentId' => [
                'nullable',
                new StringRegex,
            ],
            'name' => [
                'required',
                'string',
                'max:20',
                new StringRegex,
            ],
            'type' => 'required|in:0,1',
            'path' => [
                'nullable',
                'string',
                'max:100',
            ],
            'className' => [
                'nullable',
                'string',
                'max:100',
                new StringRegex(StringRegex::TYPE_CLASS_NAME),
            ],
            'order'   => 'nullable|integer|min:0',
            'enabled' => 'required|in:0,1',
            'remark'  => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
        if (isset($data['parentId'])) {
            if ($data['parentId'] !== 0) {
                $validatorArr['parentId'] = 'nullable|exists:view,id';
            }
        }
        (new static($data, $validatorArr))->check();
    }

    /**
     * 檢查修改 View 頁面的資料是否正確.
     *
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:view,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    /**
     * 檢查取得 View 列表的資料是否正確.
     *
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkGetViewList($data)
    {
        (new static($data, [
            'name'    => 'nullable|string|max:20',
            'enabled' => 'nullable|in:-1,0,1',
            'page'    => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]))->check();
    }

    /**
     * 檢查取得所有 View 列表(不分頁)的資料是否正確.
     *
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkGetViewOptions($data)
    {
        (new static($data, [
            'type' => 'nullable|in:-1,0,1',
        ]))->check();
    }
}
