<?php

namespace App\Validators\ActivityWallet;

use App\Rules\QuestNameRegex;
use App\Rules\StringRegex;
use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\AllowFranchisee;

class ActivityWalletProductValidator extends BaseValidator
{
    /**
     * 檢查 product 的新增資料是否正確
     * 固定型的驗證.
     *
     * @param array $data
     * @return void
     */
    public static function checkProductFixedAdd($data)
    {
        (new static($data, [
            'agentId'              => 'nullable|string',
            'clubRankId'           => 'nullable|string',
            'memberId'             => 'nullable|string',
            'name'                 => [
                'required',
                'string',
                'max:9',
            ],
            'price'                => 'required|numeric|min:0',
            'points'               => 'required|numeric|min:0',
            'waterLimit'           => 'required|numeric',
            'platforms'            => [
                'required',
                'json',
                // new StringRegex,
            ],
            'startAt'              => 'nullable|date_format:Y-m-d H:i:s',
            'endAt'                => 'nullable|date_format:Y-m-d H:i:s',
            'limitFrequency'       => 'required|integer|min:0',
            'limitMemberFrequency' => 'required|integer|min:0',
            'limitGiveUp'          => 'required|integer|min:0',
            'enabled'              => 'in:0,1',
            'image'                => 'nullable|image',
        ]))->check();
    }

    /**
     * 百分比型的驗證.
     *
     * @param array $data
     * @return void
     */
    public static function checkProductPercentAdd($data)
    {
        (new static($data, [
            'agentId'              => 'nullable|string',
            'clubRankId'           => 'nullable|string',
            'memberId'             => 'nullable|string',
            'name'                 => [
                'required',
                'string',
                'max:9',
            ],
            'percentPoints'        => 'required|numeric|min:0',
            'percentWater'         => 'required|numeric|min:0',
            'percentMinPrice'      => 'required|numeric',
            'percentMaxPoints'     => 'required|numeric',
            'platforms'            => [
                'required',
                'json',
                // new StringRegex,
            ],
            'startAt'              => 'nullable|date_format:Y-m-d H:i:s',
            'endAt'                => 'nullable|date_format:Y-m-d H:i:s',
            'limitFrequency'       => 'required|integer|min:0',
            'limitMemberFrequency' => 'required|integer|min:0',
            'limitGiveUp'          => 'required|integer|min:0',
            'enabled'              => 'in:0,1',
            'image'                => 'nullable|image',
        ]))->check();
    }

    /**
     * 檢查 product 的修改資料是否正確.
     *
     * @param array $data
     * @return void
     */
    public static function checkProductFixedModify($data)
    {
        (new static($data, [
            'id'                   => 'required|integer|min:0',
            'name'                 => [
                'required',
                'string',
                'max:9',
            ],
            'price'                => 'required|numeric|min:0',
            'points'               => 'required|numeric|min:0',
            'waterLimit'           => 'required|numeric',
            'platforms'            => [
                'required',
                'json',
            ],
            'startAt'              => 'nullable|date_format:Y-m-d H:i:s',
            'endAt'                => 'nullable|date_format:Y-m-d H:i:s',
            'limitFrequency'       => 'required|integer|min:0',
            'limitMemberFrequency' => 'required|integer|min:0',
            'limitGiveUp'          => 'required|integer|min:0',
            'enabled'              => 'in:0,1',
        ]))->check();
    }

    /**
     * 檢查 product 的修改資料是否正確.
     *
     * @param array $data
     * @return void
     */
    public static function checkProductPercentModify($data)
    {
        (new static($data, [
            'id'                   => 'required|integer|min:0',
            'name'                 => [
                'required',
                'string',
                'max:9',
            ],
            'percentPoints'        => 'required|numeric|min:0',
            'percentWater'         => 'required|numeric|min:0',
            'percentMinPrice'      => 'required|numeric',
            'percentMaxPoints'     => 'required|numeric',
            'platforms'            => [
                'required',
                'json',
            ],
            'startAt'              => 'nullable|date_format:Y-m-d H:i:s',
            'endAt'                => 'nullable|date_format:Y-m-d H:i:s',
            'limitFrequency'       => 'required|integer|min:0',
            'limitMemberFrequency' => 'required|integer|min:0',
            'limitGiveUp'          => 'required|integer|min:0',
            'enabled'              => 'in:0,1',
        ]))->check();
    }

    /**
     * 檢查圖片.
     */
    public static function checkImage($data)
    {
        (new static($data, [
            'image' => 'nullable|image',
        ]))->check();
    }

    /**
     * 檢查 product 的修改狀態資料是否正確.
     *
     * @param array $data
     * @return void
     */
    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkList($data)
    {
        (new static($data, [
            'currentFranchiseeId' => 'nullable|integer',
            'name'                => ['nullable', 'string', new StringRegex],
            'page'                => 'nullable|integer|min:1',
            'perPage'             => 'nullable|integer|min:1',
        ]))->check();
    }

    public static function checkLogList($data)
    {
        (new static($data, [
            'franchiseeId' => 'nullable|integer',
            'account'      => ['nullable', 'string', new StringRegex],
            'receive'      => ['required', 'string', new StringRegex],
            'startTime'    => 'nullable|date_format:Y-m-d H:i:s',
            'endTime'      => 'nullable|date_format:Y-m-d H:i:s',
            'page'         => 'nullable|integer|min:1',
            'perPage'      => 'nullable|integer|min:1',
        ]))->check();
    }

    public static function checkCancel($data)
    {
        (new static($data, [
            'amount' => 'required|numeric|min:0',
            'water'  => 'required|numeric|min:0',
            'reason' => ['nullable', 'string'],
        ]))->check();
    }
}
