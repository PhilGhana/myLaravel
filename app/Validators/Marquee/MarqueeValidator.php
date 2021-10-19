<?php

namespace App\Validators\Marquee;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;

class MarqueeValidator extends BaseValidator
{
    private static $marqueeData = [
        'franchiseeId' => 'required|integer',
        'suitable'     => 'required|in:member,agent',
        'type'         => 'required|in:hot,normal,deposit',
        'content'      => 'required|string',
        'startTime'    => 'nullable|date',
        'endTime'      => 'nullable|date',
        'enabled'      => 'required|in:0,1',
    ];

    public static function checkAddMarquee($data)
    {
        $checkData = [
            'franchiseeId' => 'required|integer',
            'suitable'     => 'required|in:member,agent',
            'type'         => 'required|in:hot,normal,deposit',
            'content'      => ['required', 'string'],
            'startTime'    => 'nullable|date',
            'endTime'      => 'nullable|date',
            'enabled'      => 'required|in:0,1',
        ];

        (new static($data, $checkData))->check();
    }

    public static function checkEditMarquee($data)
    {
        $checkData            = static::$marqueeData;
        $checkData['id']      = 'required|exists:marquee,id';
        $checkData['content'] = ['required', 'string'];

        (new static($data, $checkData))->check();
    }

    public static function checkToggleEnable($data)
    {
        (new static($data, [
            'id'      => 'required|exists:marquee,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkGetMarqueeList($data)
    {
        (new static($data, [
            'suitable' => 'nullable|string|in:member,agent',
            'type'     => 'nullable|string|in:hot,normal,deposit',
            'enabled'  => 'nullable|in:-1,0,1',
            'page'     => 'nullable|integer|min:1',
            'perPage'  => 'nullable|integer|min:1',
        ]))->check();
    }

    public static function checkTelebotPush($data)
    {
        (new static($data, [
            'id' => 'required|integer|min:1',
        ]))->check();
    }
}
