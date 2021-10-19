<?php
namespace App\Validators\Coupon;

use App\Validators\BaseValidator;

class CouponValidator extends BaseValidator
{
    private static $couponData = [
        'name' => 'required|string|max:50',
        'groupId' => 'required|exists:coupon_group,id',
        'type' => 'required|string|in:transfer,rescue,free,deposit',
        'image' => 'nullable|image',
        // 'platformId' => 'required|exists:game_platform,id',
        'suitableType' => 'required|string|in:all,agent,club-rank',
        'bonusType' => 'required|in:percent,amount',
        'bonusPercent' => 'nullable|numeric|min:0',
        'bonusAmount' => 'nullable|numeric|min:0',
        'bonusMax' => 'nullable|numeric|min:0',
        'amountMax' => 'nullable|numeric|min:0',
        'amountMin' => 'nullable|numeric|min:0',
        'betValidMultiple' => 'required|numeric|min:1',
        'maxTimesDay' => 'required|integer|min:0',
        'maxTimesTotal' => 'required|integer|min:0',
        'startTime' => 'required|date_format:Y-m-d H:i:s',
        'endTime' => 'required|date_format:Y-m-d H:i:s',
        'memberRegisterStart' => 'nullable|date',
        'memberRegisterEnd' => 'nullable|date',
        'content' => 'nullable|string',
        'enabled' => 'required|in:0,1',
        'remark' => 'nullable|string|max:50',
    ];

    public static function checkAddCoupon($data)
    {
        $checkArr = static::$couponData;

        switch ($data['suitableType'] ?? null) {
            case 'agent':
                $checkArr['agents'] = 'required|array|exists:agent,id';
                break;
            case 'club-rank':
                $checkArr['clubRanks'] = 'required|array|exists:club_rank,id';
                break;
        }
        $type = $data['type'] ?? null;
        if (in_array($type, ['transfer', 'rescue'])) {
            $checkArr['platformId'] = 'required|exists:game_platform,id';
        }
        (new static($data, $checkArr))->check();
    }

    public static function checkEditCoupon($data)
    {
        $checkArr = static::$couponData;
        $checkArr['id'] = 'required|exists:coupon,id';

        # edit
        unset($checkArr['type']);

        switch ($data['suitableType'] ?? null) {
            case 'agent':
                $checkArr['agents'] = 'required|array|exists:agent,id';
                break;
            case 'club-rank':
                $checkArr['clubRanks'] = 'required|array|exists:club_rank,id';
                break;
        }
        (new static($data, $checkArr))->check();
    }

    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id' => 'required|exists:coupon,id',
            'enabled' => 'required|in:0,1'
        ]))->check();
    }

    public static function checkGetList($data)
    {
        (new static($data, [
            'type' => 'nullable|in:all,transfer,rescue,free,deposit',
            'platformId' => 'nullable|exists:game_platform,id',
            'groupId' => 'nullable|exists:coupon_group,id',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1'
        ]))->check();
    }

    public static function checkGetAgents($data)
    {
        (new static($data, [
            'name' => 'nullable|string',
            'account' => 'nullable|string'
        ]))->check();
    }
}
