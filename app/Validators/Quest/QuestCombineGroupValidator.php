<?php

namespace App\Validators\Quest;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\ArrayExists;
use App\Validators\ExpansionRules\IntegerArray;
use Illuminate\Validation\Rule;

class QuestCombineGroupValidator extends BaseValidator
{
    /**
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkAdd($data)
    {
        $val = [
            'franchiseeId' => 'required|numeric',
            'name'         => ['required', 'string'],
            'enabled'      => 'required|in:0,1',
            'order'        => 'nullable|integer|min:0',
        ];

        if ($data['franchiseeId'] ?? '' !== 0) {
            $val['franchiseeId'] = 'exists:franchisee,id';
        }

        (new static($data, $val))->check();
    }

    public static function checkEdit($data)
    {
        (new static($data, [
            'id'      => 'required|exists:quest_reward_combine_groups,id',
            'name'    => ['required', 'string'],
            'enabled' => 'required|in:0,1',
            'order'   => 'nullable|integer|min:0',
        ]))->check();
    }

    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:quest_reward_combine_groups,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkDelete($data)
    {
        (new static($data, [
            'id' => 'required|exists:quest_reward_combine_groups,id',
        ]))->check();
    }

    public static function checkListOption($data)
    {
        $val = [
            'franchiseeId' => 'required|numeric',
        ];

        if ($data['franchiseeId'] ?? '' !== 0) {
            $val['franchiseeId'] = 'exists:franchisee,id';
        }

        (new static($data, $val))->check();
    }
}
