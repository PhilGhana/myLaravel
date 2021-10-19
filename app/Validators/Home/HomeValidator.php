<?php

namespace App\Validators\Home;

use App\Validators\BaseValidator;
use Carbon\Carbon;

class HomeValidator extends BaseValidator
{
    public static function fetch($data)
    {
        $agent             = user()->model();
        $stime             = Carbon::parse($data['stime'])->addMonths(3);
        $etime             = Carbon::parse($data['etime']);
        $data['timeRange'] = $stime->diffInDays($etime, \false);

        $validatorArr = [
            'stime'        => 'required|date_format:Y-m-d H:i:s',
            'etime'        => 'required|date_format:Y-m-d H:i:s',
            'franchiseeId' => 'required|integer',
            'timeRange'    => 'required|integer|lte:0',
            'level'        => 'nullable|integer',
            'agentId'      => 'nullable|array',
            'parentId'     => 'nullable|integer',
        ];

        if (! $agent->isCompany()) {
            // franchiseeId 要和登入帳號一致
            $validatorArr['franchiseeId'] = 'required|integer|exists:franchisee,id|size:'.$agent->franchisee_id;
        } elseif ($data['franchiseeId'] != 0) {
            $validatorArr['franchiseeId'] = 'required|integer|exists:franchisee,id';
        }

        (new static($data, $validatorArr))->check();
    }

    public static function agentList($data)
    {
        $agent = user()->model();

        $validatorArr = [
            'franchiseeId' => 'required|integer',
            'level'        => 'nullable|integer',
            'agentId'      => 'nullable|integer',
            'parentId'     => 'nullable|integer',
        ];

        if (! $agent->isCompany()) {
            $validatorArr['franchiseeId'] = 'required|integer|exists:franchisee,id|size:'.$agent->franchisee_id;
        } elseif ($data['franchiseeId'] != 0) {
            $validatorArr['franchiseeId'] = 'required|integer|exists:franchisee,id';
        }

        (new static($data, $validatorArr))->check();
    }
}
