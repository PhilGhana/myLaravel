<?php
namespace App\Validators\Game;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\ArrayExists;
use App\Validators\ExpansionRules\IntegerArray;
use Illuminate\Validation\Rule;

class GameValidator extends BaseValidator
{
    /**
     * 檢查 game 的儲存資料是否正確
     *
     * @param array $data 資料陣列
     * @return void
     */

    public static function checkAdd($data)
    {
        $pid = $data['platformId'] ?? 0;

        (new static($data, [
            'platformId' => 'required|exists:game_platform,id',
            'type'       => 'required|exists:game_type',
            'code'       => [
                'required',
                'max:40',
                Rule::unique('game')->where(function ($query) use ($pid) {
                    $query->where('platform_id', $pid);
                }),
            ],
            'tags'       => [
                'nullable',
                'array',
                Rule::exists('game_tag', 'tag'),
            ],
            'order'      => 'required|numeric|min:0',
            'maintain'   => 'required|in:0,1',
            'image'      => 'nullable|mimes:jpeg,jpg,png,gif',
            'enabled'    => 'required|in:0,1',
            'remark'     => ['nullable','string','max:100'],
        ]))->check();
    }

    /**
     * 檢查 game 的儲存資料是否正確
     *
     * @param array $data 資料陣列
     * @return void
     */
    public static function checkEdit($data)
    {
        $id         = $data['id'] ?? 0;
        $platformId = intval($data['platformId'] ?? 0);

        $valids = [
            'id'       => 'required|exists:game,id',
            'type'     => 'required|exists:game_type',
            'code'     => [
                'required',
                'max:40',
                Rule::unique('game')->where(function ($query) use ($id, $platformId) {
                    return $query->where('platform_id', '=', $platformId)
                        ->where('id', '!=', $id);
                }),
            ],
            'tags'     => [
                'nullable',
                'array',
                'exists:game_tag,tag',
            ],
            'order'    => 'required|numeric|min:0',
            'maintain' => 'required|in:0,1',
            'image'    => 'nullable|mimes:jpeg,jpg,png,gif',
            'enabled'  => 'required|in:0,1',
            'remark'   => ['nullable','string','max:100'],
        ];

        (new static($data, $valids))->check();
    }

    public static function checkToggleEnable($data)
    {
        (new static($data, [
            'id'      => 'required|exists:game,id',
            'enabled' => 'required|in:0,1',
        ]))->check();
    }

    public static function checkGameList($data)
    {
        $valids = [
            'platformId' => 'nullable|numeric',
            'name'       => ['nullable','string','max:20', new StringRegex],
            'enabled'    => 'nullable|in:-1,0,1',
            'type'       => 'nullable|in:hot,recent',
            'perPage'    => 'nullable|integer|min:0',
            'page'       => 'nullable|integer|min:0',
        ];

        # 若 limit 限制 = 1, 則 members 參數為必需
        if (isset($data['limit']) && intval($data['limit']) === 1) {
            $valids['members'] = ['required', new IntegerArray()];
        }
        (new static($data, $valids))->check();
    }

    public static function checkGameAll($data)
    {
        (new static($data, [
            'platformId' => 'required|numeric',
        ]))->check();
    }

    public static function checkBatchToggleMaintain($data)
    {
        (new static($data, [
            'tags'     => 'nullable|array',
            'tags.*'   => 'nullable|exists:game_tag,tag',
            'types'    => 'nullable|array',
            'type.*'   => 'nullable|exists:game_type,type',
            'maintain' => 'required|in:0,1',
        ]))->check();
    }
}
