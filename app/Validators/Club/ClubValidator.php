<?php

namespace App\Validators\Club;

use App\Exceptions\FailException;
use App\Rules\StringRegex;
use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\IntegerArray;

class ClubValidator extends BaseValidator
{
    private static $clubData = [
        'name'         => 'required|string|max:20',
        'enabled'      => 'required|in:0,1',
        'describe'     => 'nullable|string|max:50',
        'remark'       => 'nullable|string|max:50',
        'franchiseeId' => 'required|exists:franchisee,id',
        'fullpay'      => 'string|max:15',
    ];

    /**
     * 檢查 Club 的新增資料是否正確.
     *
     * @param array $data
     * @return void
     */
    public static function checkClubAdd($data)
    {
        $cloneData          = $data;
        $cloneData['games'] = ($data['games'] ?? null) ? explode(',', $data['games']) : [];

        (new static($cloneData, [
            'name'         => ['required', 'string', 'max:20', new StringRegex],
            'enabled'      => 'required|in:0,1',
            'describe'     => ['nullable', 'string', 'max:50', new StringRegex],
            'remark'       => ['nullable', 'string', 'max:50'],
            'franchiseeId' => 'required|exists:franchisee,id',
            'fullpay'      => ['string', 'max:15', new StringRegex(StringRegex::TYPE_CHAR_NUMBER_ONLY)],
            'games'        => ['nullable', new IntegerArray, 'exists:game,id'],
        ]))->check();
    }

    /**
     * 檢查 Club 的修改資料是否正確.
     *
     * @param array $data
     * @return void
     */
    public static function checkClubEdit($data)
    {
        $cloneData          = $data;
        $cloneData['games'] = ($data['games'] ?? null) ? explode(',', $data['games']) : [];

        (new static($cloneData, [
            'name'         => ['required', 'string', 'max:20', new StringRegex],
            'enabled'      => 'required|in:0,1',
            'describe'     => ['nullable', 'string', 'max:50', new StringRegex],
            'remark'       => ['nullable', 'string', 'max:50'],
            'franchiseeId' => 'required|exists:franchisee,id',
            'fullpay'      => ['string', 'max:15', new StringRegex(StringRegex::TYPE_CHAR_NUMBER_ONLY)],
            'id'           => 'required|integer|exists:club,id',
            'games'        => ['nullable', new IntegerArray, 'exists:game,id'],
        ]))->check();
    }

    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|integer|exists:club,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    private static $clubRankData = [
        'name'             => 'required|string|max:20',
        'enabled'          => 'required|in:0,1',
        'order'            => 'nullable|integer|min:0',
        'depositPerMax'    => 'required|numeric|min:0',
        'depositPerMin'    => 'required|numeric|min:0',
        'depositDayTimes'  => 'required|integer|min:0',
        'withdrawPerMax'   => 'required|numeric|min:0',
        'withdrawPerMin'   => 'required|numeric|min:0',
        'withdrawDayTimes' => 'required|integer|min:0',
        'gameWaterPercent' => 'required|json',
        'franchiseeId'     => 'required|exists:franchisee,id',
    ];

    /**
     * 檢查 新增俱樂部層級的資料是否正確.
     *
     * @param array $data
     * @return void
     */
    public static function checkAddRankClub($data)
    {
        (new static($data, [
            'name'                              => ['required', 'string', 'max:20', new StringRegex],
            'enabled'                           => 'required|in:0,1',
            'order'                             => 'nullable|integer|min:0',
            'depositPerMax'                     => 'required|numeric|min:0',
            'depositPerMin'                     => 'required|numeric|min:0',
            'depositDayTimes'                   => 'required|integer|min:0',
            'withdrawPerMax'                    => 'required|numeric|min:0',
            'withdrawPerMin'                    => 'required|numeric|min:0',
            'withdrawDayTimes'                  => 'required|integer|min:0',
            'gameWaterPercent'                  => 'required|json',
            'clubId'                            => 'required|exists:club,id',
            'bonusLimit'                        => 'required|numeric|min:0',
            'waterLimit'                        => 'required|numeric|min:0',
            'withdrawFeePeriod'                 => 'required|in:day,week,month',
            'depositAccumulationPeriod'         => 'required|in:day,week,month',
            'withdrawAccumulationPeriod'        => 'required|in:day,week,month',
            'withdrawFee'                       => 'required|numeric|min:0',
            'withdrawFeePercent'                => 'required|numeric|min:0|max:100',
            'depositAccumulationAmount'         => 'required|numeric|min:0',
            'withdrawAccumulationAmount'        => 'required|numeric|min:0',
        ]))->check();

        static::checkConfig($data);
    }

    /**
     * 檢查 修改俱樂部層級的資料是否正確.
     *
     * @param array $data
     * @return void
     */
    public static function checkEditRankClub($data)
    {
        $checkData = [
            'name'             => ['required', 'string', 'max:20', new StringRegex],
            'enabled'          => 'required|in:0,1',
            'order'            => 'nullable|integer|min:0',
            'depositPerMax'    => 'required|numeric|min:0',
            'depositPerMin'    => 'required|numeric|min:0',
            'depositDayTimes'  => 'required|integer|min:0',
            'withdrawPerMax'   => 'required|numeric|min:0',
            'withdrawPerMin'   => 'required|numeric|min:0',
            'withdrawDayTimes' => 'required|integer|min:0',
            // 'gameWaterPercent' => 'required|json',
            'franchiseeId'                      => 'required|exists:franchisee,id',
            'bonusLimit'                        => 'required|numeric|min:0',
            'waterLimit'                        => 'required|numeric|min:0',
            'withdrawFeePeriod'                 => 'required|in:day,week,month',
            'depositAccumulationPeriod'         => 'required|in:day,week,month',
            'withdrawAccumulationPeriod'        => 'required|in:day,week,month',
            'withdrawFee'                       => 'required|numeric|min:0',
            'withdrawFeePercent'                => 'required|numeric|min:0|max:100',
            'depositAccumulationAmount'         => 'required|numeric|min:0',
            'withdrawAccumulationAmount'        => 'required|numeric|min:0',

        ];
        $checkData['id']                = 'required|exists:club_rank,id';
        $checkData['bankLimit']         = 'required|integer|min:0';
        $checkData['withdrawFreeTimes'] = 'required|integer|min:0';

        (new static($data, $checkData))->check();

        // static::checkConfig($data);
    }

    public static function checkEditRankClubWater($data)
    {
        (new static($data, [
            'id'               => 'required|exists:club_rank,id',
            'gameWaterPercent' => 'required|json',
        ]))->check();
        static::checkConfig($data);
    }

    private static function checkConfig($data)
    {
        $configs = collect(json_decode($data['gameWaterPercent'], true))->pluck('waterPercent', 'id')->toArray();
        $ids     = array_keys($configs);
        $waters  = array_values($configs);

        $errors  = [];
        $newData = array_combine($ids, $waters);
        foreach ($newData as $key => $value) {
            if ($value === '' || $value > 100 || $value < 0) {
                $errors["water-{$key}"] = __('franchisee.invalid-number');
            }
        }

        if (count($errors)) {
            $exception = new FailException('water percent invalid');
            $exception->setErrors($errors);
            throw $exception;
        }
    }

    public static function checkToggleEnabledClubRank($data)
    {
        (new static($data, [
            'id'      => 'required|exists:club_rank,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    /**
     * 檢查 取得俱樂部列表 資料是否正確.
     *
     * @param [type] $data
     * @return void
     */
    public static function checkListClub($data)
    {
        (new static($data, [
            'name'    => 'nullable|string|max:20',
            'enabled' => 'nullable|in:-1,0,1',
            'page'    => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]))->check();
    }

    public static function checkClubRankDefaultEdit($data)
    {
        (new static($data, [
            'clubRankId' => 'required|exists:club_rank,id',
        ]))->check();
    }

    public static function autoWater($data)
    {
        (new static($data, [
            'auto_water'   => 'required|integer',
            'auto_waterAt' => 'date_format:H:i|nullable',
        ]))->check();
    }

    public static function upgrade($data)
    {
        (new static($data, [
            'upgrade_by_deposit'          => 'nullable|between:0,999999999999999999.9999',
            'upgrade_by_withdraw'         => 'nullable|between:0,999999999999999999.9999',
            'upgrade_by_total_bet_amount' => 'nullable|between:0,999999999999999999.9999',
        ]))->check();
    }
}
