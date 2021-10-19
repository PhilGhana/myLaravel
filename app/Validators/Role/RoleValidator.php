<?php

namespace App\Validators\Role;

use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\IntegerArray;
use App\Validators\ExpansionRules\SortArray;

class RoleValidator extends BaseValidator
{
    public static function checkAddRole($data)
    {
        $minRank = user()->model()->role->rank + 1;
        (new static($data, [
            'name'     => [
                'required',
                'string',
                'max:20',
            ],
            'rank'     => "required|integer|min:{$minRank}",
            'enabled'  => 'required|in:0,1',
            'fullIp'   => 'in:0,1',
            'fullInfo' => 'in:0,1',
        ]))->check();
    }

    public static function checkEditRole($data)
    {
        $rank    = user()->model()->role->rank;
        $minRank = $rank === 0 ? 0 : $rank + 1;
        (new static($data, [
            'id'       => 'required|exists:role,id',
            'name'     => [
                'required',
                'string',
                'max:20',
            ],
            'rank'     => "required|integer|min:{$minRank}",
            'enabled'  => 'required|in:0,1',
            'fullIp'   => 'in:0,1',
            'fullInfo' => 'in:0,1',
        ]))->check();
    }

    public static function checkToggleEnable($data)
    {
        (new static($data, [
            'id'      => 'required|exists:role,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkGetRoleList($data)
    {
        (new static($data, [
            'name'    => 'nullable|string|max:20',
            'enabled' => 'nullable|in:-1,0,1',
            'sorts'   => ['nullable', new SortArray(['id', 'rank', 'name', 'updatedAt'])],
            'page'    => 'nullable|integer',
            'perPage' => 'nullable|integer',
        ]))->check();
    }

    public static function checkGetRoleAll($data)
    {
        (new static($data, [
            'enabled' => 'nullable|in:-1,0,1',
        ]))->check();
    }

    public static function checkEditAuth($data)
    {
        (new static($data, [
            'id'    => 'required|exists:role,id',
            'views' => ['required', new IntegerArray],
            'apis'  => ['required', new IntegerArray],
        ]))->check();
    }
    public static function checkToggleCommon($data)
    {
        (new static($data, [
            'id'     => 'required|exists:role,id',
            'common' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkToggleWhitelist($data)
    {
        (new static($data, [
            'id'      => 'required|exists:role,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkAddWhitelist($data)
    {
        (new static($data, [
            'id' => 'required|exists:role,id',
            'ip' => 'required|string|max:100',
        ]))->check();
    }

    public static function checkToggleFullIp($data)
    {
        (new static($data, [
            'id'     => 'required|exists:role,id',
            'fullIp' => 'required|in:1,0',
        ]));
    }
    public static function checkToggleFullInfo($data)
    {
        (new static($data, [
            'id'       => 'required|exists:role,id',
            'fullInfo' => 'required|in:1,0',
        ]));
    }
    public static function checkEditSubRole($data)
    {
        (new static($data, [
            'id'       => 'required|exists:role,id',
            'name'     => [
                'required',
                'string',
                'max:20',
            ],
            'enabled'  => 'required|in:0,1',
            'fullIp'   => 'in:0,1',
            'fullInfo' => 'in:0,1',
        ]))->check();
    }
}