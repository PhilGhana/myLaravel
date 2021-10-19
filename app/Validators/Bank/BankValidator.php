<?php

namespace App\Validators\Bank;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;

class BankValidator extends BaseValidator
{
    public static function checkAddBank($data)
    {
        $validator = [
            'franchiseeId' => 'required|integer',
            'type'         => 'required|in:deposit,withdraw',
            'suitable'     => 'required|in:agent,member',
            'name'         => ['required', 'string', 'max:20', new StringRegex],
            'account'      => ['required', 'string', 'max:50', new StringRegex(StringRegex::TYPE_CHAR_NUMBER_ONLY)],
            'bankName'     => ['required', 'string', 'max:50', new StringRegex],
            'branchName'   => ['nullable', 'string', 'max:50', new StringRegex],
            'enabled'      => 'required|in:0,1',
        ];
        if ($data['type'] == 'deposit') {
            $validator['limitAmount'] = 'required|numeric|min:0';
        } else {
            $validator['limitAmount'] = 'required|numeric|max:0';
        }

        $suitable             = $data['suitable'] ?? null;
        $useable              = $data['useable'] ?? null;
        $validator['useable'] = $suitable === 'agent'
        ? 'required|in:all,agent'
        : 'required|in:all,agent,club-rank,bank-group';

        if ($useable === 'agent') {
            $validator['agents'] = 'required|array|exists:agent,id';
        } elseif ($useable === 'club-rank') {
            $validator['clubRanks'] = 'required|array|exists:club_rank,id';
        } elseif ($useable === 'bank-group') {
            $validator['bankGroups'] = 'required|array|exists:bank_group,id';
        }

        (new static($data, $validator))->check();
    }

    public static function checkEditBank($data)
    {
        $validator = [
            'id'       => 'required|exists:bank,id',
            'type'     => 'required|in:deposit,withdraw',
            'suitable' => 'required|string|in:agent,member',
            'useable'  => 'required|in:all,agent,club-rank,bank-group',
            'enabled'  => 'required|in:0,1',
        ];

        $suitable             = $data['suitable'] ?? null;
        $useable              = $data['useable'] ?? null;
        $validator['useable'] = $suitable === 'agent'
        ? 'required|in:all,agent'
        : 'required|in:all,agent,club-rank,bank-group';

        if ($useable === 'agent') {
            $validator['agents'] = 'required|array|exists:agent,id';
        } elseif ($useable === 'club-rank') {
            $validator['clubRanks'] = 'required|array|exists:club_rank,id';
        } elseif ($useable === 'bank-group') {
            $validator['bankGroups'] = 'required|array|exists:bank_group,id';
        }

        (new static($data, $validator))->check();
    }

    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:bank,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkGetBankList($data)
    {
        (new static($data, [
            'enabled'  => 'nullable|in:-1,0,1',
            'type'     => 'nullable|in:deposit,withdraw',
            'suitable' => 'nullable|in:all,agent,member',
            'name'     => ['nullable', 'string', new StringRegex],
            'bankName' => ['nullable', 'string', new StringRegex],
            'page'     => 'nullable|integer|min:1',
            'perPage'  => 'nullable|integer|min:1',
        ]))->check();
    }

    public static function checkUpdateCumulativeAmount($data)
    {
        (new static($data, [
            'id'    => 'required|exists:bank,id',
            'value' => 'required|integer',
        ]))->check();
    }

    public static function checkDeleteBank($data)
    {
        (new static($data, [
            'id'  => 'required|exists:bank,id',
            'fid' => 'required|integer',
        ]))->check();
    }

    public static function checkUpdatelimitAmount($data)
    {
        $validator['limitAmount'] = 'required|numeric|min:0';
        // if ($data['type'] == 'deposit') {
        //     $validator['limitAmount'] = "required|numeric|min:0";
        // } else {
        //     $validator['limitAmount'] = "required|numeric|max:0";
        // }
        (new static($data, $validator))->check();
    }
}
