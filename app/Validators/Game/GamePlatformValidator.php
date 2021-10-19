<?php

namespace App\Validators\Game;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\ArrayExists;
use App\Validators\ExpansionRules\IntegerArray;
use Illuminate\Validation\Rule;

class GamePlatformValidator extends BaseValidator
{
    private static $platformData = [
        'key'             => 'required|string|max:20',
        'name'            => 'required|string|max:20',
        'memberPrefix'    => 'nullable|string',
        'setting'         => 'nullable|json',
        'enabled'         => 'required|in:0,1',
        'fun'             => 'required|in:0,1',
        'limit'           => 'required|in:0,1',
        'maintain'        => 'required|in:0,1',
        'maintainCrontab' => 'nullable|string',
        'maintainMinute'  => 'nullable|integer|min:0',
        'image'           => 'nullable|image',
        'icon'            => 'nullable|image',
        'order'           => 'required|numeric|min:0',
    ];

    /**
     * 檢查 platform 的儲存資料是否正確.
     *
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkAdd($data)
    {
        $valids = [
            'key'                      => ['required', 'string', 'max:20', new StringRegex(StringRegex::TYPE_CHAR_NUMBER_ONLY)],
            'name'                     => ['required', 'string', 'max:20', new StringRegex(StringRegex::TYPE_GAME_PLATFORM_NAME)],
            'memberPrefix'             => ['nullable', 'string', new StringRegex(StringRegex::TYPE_GAME_PLATFORM_PREFIX)],
            'setting'                  => 'nullable|json',
            'enabled'                  => 'required|in:0,1',
            'fun'                      => 'required|in:0,1',
            'limit'                    => 'required|in:0,1',
            'maintain'                 => 'required|in:0,1',
            'maintainCrontab'          => ['nullable', 'string'],
            'maintainMinute'           => 'nullable|integer|min:0',
            'image'                    => 'nullable|mimes:jpeg,jpg,png,gif',
            'icon'                     => 'nullable|mimes:jpeg,jpg,png,gif',
            'order'                    => 'required|numeric|min:0',
            'disposableMaintainDate'   => 'nullable||date_format:Y-m-d H:i',
            'disposableMaintain'       => 'nullable|integer|min:0',
        ];

        // 若 limit 限制 = 1, 則 members 參數為必需
        if (intval($data['limit'] ?? 0) === 1) {
            $valids['members'] = 'required|array|exists:member,id';
        }

        // 若 fun 試玩版 = 1, 則 platformId 為必須
        if (intval($data['fun'] ?? 0) === 1) {
            $valids['platformId'] = 'required|numeric|min:1';
        }

        (new static($data, $valids))->check();
    }

    public static function checkEdit($data)
    {
        $valids = [
            'key'                      => ['required', 'string', 'max:20', new StringRegex(StringRegex::TYPE_CHAR_NUMBER_ONLY)],
            'name'                     => ['required', 'string', 'max:20', new StringRegex(StringRegex::TYPE_GAME_PLATFORM_NAME)],
            'memberPrefix'             => ['nullable', 'string', new StringRegex(StringRegex::TYPE_GAME_PLATFORM_PREFIX)],
            'setting'                  => 'nullable|json',
            'enabled'                  => 'required|in:0,1',
            'fun'                      => 'required|in:0,1',
            'limit'                    => 'required|in:0,1',
            'maintain'                 => 'required|in:0,1',
            'maintainCrontab'          => ['nullable', 'string'],
            'maintainMinute'           => 'nullable|integer|min:0',
            'image'                    => 'nullable|mimes:jpeg,jpg,png,gif',
            'icon'                     => 'nullable|mimes:jpeg,jpg,png,gif',
            'order'                    => 'required|numeric|min:0',
            'id'                       => 'required|exists:game_platform,id',
            'disposableMaintainDate'   => 'nullable||date_format:Y-m-d H:i',
            'disposableMaintain'       => 'nullable|integer|min:0',
        ];

        // 若 limit 限制 = 1, 則 members 參數為必需
        if (intval($data['limit'] ?? 0) === 1) {
            $valids['members'] = 'required|array|exists:member,id';
        }

        // 若 fun 試玩版 = 1, 則 platformId 為必須
        if (intval($data['fun'] ?? 0) === 1) {
            $valids['platformId'] = 'required|numeric|min:1';
        }

        (new static($data, $valids))->check();
    }

    public static function checkToggleEnable($data)
    {
        (new static($data, [
            'id'      => 'required|exists:game_platform,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkList($data)
    {
        (new static($data, [
            'key'     => 'nullable|string|max:10',
            'name'    => 'nullable|string|max:20',
            'type'    => 'nullable|string|max:10',
            'enabled' => 'nullable|in:-1,0,1',
        ]))->check();
    }

    public static function checkEditByLimitedAuth($data)
    {
        $valids = [
            // 'key'      => ['required', 'string', 'max:20', new StringRegex],
            // 'name'     => ['required', 'string', 'max:20', new StringRegex(StringRegex::TYPE_GAME_PLATFORM_NAME)],
            'limit'    => 'required|in:0,1',
            'maintain' => 'required|in:0,1',
            'image'    => 'nullable|mimes:jpeg,jpg,png,gif',
            'icon'     => 'nullable|mimes:jpeg,jpg,png,gif',
            'id'       => 'required|exists:game_platform,id',
        ];

        // 若 limit 限制 = 1, 則 members 參數為必需
        if (intval($data['limit'] ?? 0) === 1) {
            $valids['members'] = 'required|array|exists:member,id';
        }

        (new static($data, $valids))->check();
    }
}