<?php
namespace App\Validators\AgentCoupon;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;

class AgentCouponValidator extends BaseValidator
{
    public static function checkCommit($data)
    {
        $valids = [
            'name'                => ['required', 'string', 'max:50', new StringRegex],
            'type'                => 'required|string|in:transfer,rescue,free,deposit',
            'suitableType'        => 'required|string|in:all,agent',
            'bonusType'           => 'required|string|in:percent,amount',
            'amountMax'           => 'required|numeric|min:0',
            'amountMin'           => 'required|numeric|min:0',
            'betValidMultiple'    => 'required|numeric|min:1',
            'maxTimesDay'         => 'required|integer|min:0',
            'maxTimesTotal'       => 'required|integer|min:0',
            'startTime'           => 'required|date_format:Y-m-d H:i:s',
            'endTime'             => 'required|date_format:Y-m-d H:i:s',
            'memberRegisterStart' => 'nullable|date',
            'memberRegisterEnd'   => 'nullable|date',
            'content'             => ['nullable', 'string', new StringRegex],
            'enabled'             => 'required|in:0,1',
            'remark'              => ['nullable', 'string', 'max:50'],
        ];
        if (isset($data['suitableType'])) {
            if ($data['suitableType'] === 'agent') {
                $valids['agents'] = 'required|array|exists:agent,id';
            }
        }
        if (in_array($data['type'] ?? null, ['transfer', 'rescue'])) {
            $valids['platformId'] = 'required|exists:game_platform,id';
        }
        if ($data['bonusType'] === 'percent') {
            $valids['bonusPercent'] = 'required|numeric|min:0';
            $valids['bonusMax']     = 'required|numeric|min:0';
        } else {
            $valids['bonusAmount'] = 'required|numeric|min:0';
        }
        (new static($data, $valids))->check();
    }

    public static function checkGetList($data)
    {
        (new static($data, [
            'type'       => 'nullable|string|in:transfer,rescue,free,deposit',
            'platformId' => 'nullable|exists:game_platform,id',
            'page'       => 'nullable|integer|min:1',
            'perPage'    => 'nullable|integer|min:1',
        ]))->check();
    }
}
