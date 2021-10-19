<?php

namespace App\Validators\Agent;

use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\SortArray;

class DepositAnnouncementValidator extends BaseValidator
{
    public static function checkList($data)
    {
        (new static($data, [
            'franchiseeId' => 'required|integer',
            'agents'       => 'nullable|string',
            'sorts'        => ['nullable', new SortArray(['id', 'enabled', 'bind_agent_id', 'updated_at'])],
            'page'         => 'nullable|integer',
            'perPage'      => 'nullable|integer',
        ]))->check();
    }

    public static function checkEnabled($data)
    {
        (new static($data, [
            'id' => 'required|integer',
        ]))->check();
    }

    public static function checkAdd($data)
    {
        (new static($data, [
            'franchiseeId' => 'required|integer',
            'information'  => 'required|string',
            'bindAgents'   => 'required|string',
        ]))->check();
    }

    public static function checkEdit($data)
    {
        (new static($data, [
            'id'          => 'required|integer',
            'enabled'     => 'nullable|integer',
            'information' => 'required|string',
        ]))->check();
    }

    public static function checkGetDetail($data)
    {
        (new static($data, [
            'id' => 'required|integer',
        ]))->check();
    }

    public static function checkGetAgents($data)
    {
        (new static($data, [
            'franchiseeId' => 'required|integer',
            'displayAll'   => 'nullable|integer',
        ]))->check();
    }
}
