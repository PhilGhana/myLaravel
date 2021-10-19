<?php

namespace App\Validators\Report;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;

class ReportValidator extends BaseValidator
{

    public static function settlementDay($data)
    {
        (new static($data, [
            'date' => 'required|date_format:Y-m-d',
        ]))->check();
    }

    public static function settlementMonthList($data)
    {

        (new static($data, [
            'year' => 'required|integer',
            'month' => 'required|between:1,12',
            'level' => 'required|between:1,5',
        ]))->check();
    }

    public static function settlementMonth($data)
    {
        $total = floatval($data['total'] ?? 0);
        $rules = [
            'agentId' => 'required|exists:agent,id',
            'year' => 'required|integer',
            'month' => 'required|between:1,12',
            'total' => 'required|numeric',
            'writeOffAmount' => 'required|numeric',
        ];
        if ($total === 0.0) {
            $rules['writeOffAmount'] = 'required|numeric|not_in:0';
        }
        (new static($data, $rules))->check();
    }

    public static function checkResultAgent($data)
    {
        (new static($data, [
            'startTime' => 'required|date',
            'endTime' => 'required|date',
            'platformId' => 'required|numeric|exists:game_platform,id',
            'parentId' => 'nullable|exists:agent,id',
            'agentId' => 'nullable|exists:agent,id',
            'gameType' => 'nullable|string|exists:game_type,type',
            'gameId' => 'nullable|numeric|exists:game,id'
        ]))->check();
    }

    public static function checkResultType($data)
    {
        (new static($data, [
            'startTime' => 'required|date',
            'endTime' => 'required|date',
            'platformId' => 'required|numeric|exists:game_platform,id',
            'agentId' => 'nullable|exists:agent,id',
            'gameType' => 'nullable|string|exists:game_type,type',
        ]))->check();
    }

    public static function checkResultMember($data)
    {
        (new static($data, [
            'startTime' => 'required|date',
            'endTime' => 'required|date',
            'platformId' => 'required|numeric|exists:game_platform,id',
            'agentId' => 'nullable|exists:agent,id',
            'memberId' => 'nullable|exists:member,id',
            'gameType' => 'nullable|string|exists:game_type,type',
            'gameId' => 'nullable|exists:game,id',
        ]))->check();
    }

    public static function checkResultMemberDetail($data)
    {
        (new static($data, [
            'memberId' => 'required|exists:member,id',
            'startTime' => 'required|date',
            'endTime' => 'required|date',
            'platformId' => 'required|exists:game_platform,id',
            'gameType' => 'nullable|string|exists:game_type,type',
            'gameId' => 'nullable|numeric|exists:game,id'
        ]))->check();
    }

    public static function checkAgentOptions($data)
    {
        (new static($data, [
            'account' => 'required|string'
        ]))->check();
    }

    public static function checkMemberOptions($data)
    {
        (new static($data, [
            'account' => 'required|string'
        ]))->check();
    }

    public static function checkFindAccount($data)
    {
        (new static($data, [
            'account' => ['required', new StringRegex(StringRegex::TYPE_MEMBER_ACCOUNT)],
        ]))->check();
    }
}
