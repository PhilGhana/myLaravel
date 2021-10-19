<?php
namespace App\Validators\Franchisee;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;

class FranchiseeValidator extends BaseValidator
{
    public static function add($data)
    {
        (new static($data, [
            'name'      => ['required', 'string', 'max:20', new StringRegex],
            'enabled'   => 'required|in:0,1',
            'locked'    => 'required|in:0,1',
            'host'      => ['required', 'json'],
            'resetLock' => 'nullable|integer|min:1',
        ]))->check();
    }

    public static function edit($data)
    {
        (new static($data, [
            'id'         => 'required|exists:franchisee,id',
            'agentId'    => 'nullable|exists:agent,id,level,5',
            'clubRankId' => 'nullable|exists:club_rank,id',
            'enabled'    => 'required|in:0,1',
            'locked'     => 'required|in:0,1',
            'host'       => ['required', 'json'],
            'resetLock'  => 'nullable|integer|min:1',
        ]))->check();
    }

    public static function list($data) {
        (new static($data, [
            'name'    => 'nullable|string|max:20',
            'enabled' => 'nullable|in:-1,0,1',
            'locked'  => 'nullable|in:-1,0,1',
            'host'    => 'nullable|string|max:20',
            'page'    => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]))->check();
    }

    public static function toggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:franchisee,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function toggleLocked($data)
    {
        (new static($data, [
            'id'     => 'required|exists:franchisee,id',
            'locked' => 'required|in:0,1',
        ]))->check();
    }

    public static function percentConfig($data)
    {
        (new static($data, [
            'id' => 'required|exists:franchisee,id',
        ]))->check();
    }

    public static function multipleConfigAdd($data)
    {
        (new static($data, [
            'franchisee_id'       => 'required|integer',
            'bet_amount_multiple' => 'required|numeric|between:0,999999.99',
            'weights'             => 'required|integer',
            'start_time'          => 'date|nullable',
            'end_time'            => 'date|nullable',
        ]))->check();
    }

    public static function multipleConfigEdit($data)
    {
        (new static($data, [
            'franchisee_id'       => 'required|integer',
            'bet_amount_multiple' => 'required|numeric|between:0,999999.99',
            'weights'             => 'required|integer',
            'start_time'          => 'date|nullable',
            'end_time'            => 'date|nullable',
        ]))->check();
    }

    public static function franchiseeRation($data)
    {
        (new static($data, [
            'auto_bonus'    => 'required|integer',
            'auto_bonus_at' => 'date_format:H:i|nullable',
        ]))->check();
    }
}
