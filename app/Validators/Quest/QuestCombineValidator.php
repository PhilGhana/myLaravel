<?php

namespace App\Validators\Quest;

use App\Models\QuestRewardCombine;
use App\Rules\QuestNameRegex;
use App\Validators\BaseValidator;
use Lang;

class QuestCombineValidator extends BaseValidator
{
    private static $addData = [
        'franchiseeId'       => 'nullable',
        'agentId'            => 'nullable|string',
        'clubRankId'         => 'nullable|string',
        'memberId'           => 'nullable|string',
        'enabled'            => 'required|boolean',
        'name'               => 'required|string|max:191',
        'condition'          => 'required|string',
        'method'             => 'required|string',
        'type'               => 'required|string',
        'reward'             => 'nullable',
        'percentScale'       => 'nullable',
        'limitTimes'         => 'required|integer|min:1',
        'period'             => 'nullable|string',
        'endTime'            => 'nullable|date',
        'startTime'          => 'nullable|date',
        'imageSmall'         => 'nullable|image',
        'imageLarge'         => 'nullable|image',
        'information'        => 'nullable|string',
        'informationDisplay' => 'required|boolean',
        'gamesId'            => 'nullable|string',
        'checkDeposit'       => 'nullable',
        'lockAmount'         => 'nullable|numeric|min:0.0001',
        'lockAmountScale'    => 'required|string',
        'triggerForPeriod'   => 'nullable|integer|min:1',
    ];

    private static $editData = [
        'id'                 => 'required|exists:quest_reward_combine,id',
        'enabled'            => 'required|boolean',
        'name'               => 'required|string|max:191',
        'imageSmall'         => 'nullable',
        'imageLarge'         => 'nullable',
        'information'        => 'nullable|string',
        'informationDisplay' => 'required|boolean',
    ];

    private static $reportTotal = ['profit-report-total', 'loss-report-total'];

    public static function addQuest($data)
    {
        $arr = [
            'franchiseeId'        => 'nullable',
            'agentId'             => 'nullable|string',
            'clubRankId'          => 'nullable|string',
            'memberId'            => 'nullable|string',
            'enabled'             => 'required|boolean',
            'name'                => ['required', 'string', 'max:191'],
            'condition'           => 'required|string',
            'method'              => 'required|string',
            'type'                => 'required|string',
            'reward'              => 'nullable',
            'percentScale'        => 'nullable',
            'limitTimes'          => 'required|integer|min:1',
            'period'              => 'nullable|string',
            'endTime'             => 'nullable|date',
            'startTime'           => 'nullable|date',
            'imageSmall'          => 'nullable|mimes:jpeg,bmp,png,gif,jpg',
            // 'imageLarge'          => 'nullable|mimes:jpeg,bmp,png,gif,jpg',
            'information'         => 'nullable|string',
            'informationDisplay'  => 'required|boolean',
            'platformId'          => 'nullable|string',
            'checkDeposit'        => 'nullable',
            'lockAmount'          => 'nullable|numeric|min:0.0001',
            'lockAmountScale'     => 'required|string',
            'triggerForPeriod'    => 'nullable|integer|min:1',
            'existForPeriod'      => 'nullable|integer|min:1',
            'daysAfterRegistered' => 'nullable|integer|min:1',
        ];
        // 如果有套用群組, 就檢查
        if ((int) $data['groupId'] !== 0) {
            $arr = [
                'groupId' => 'nullable|exists:quest_reward_combine_groups,id',
            ];
        }

        $msg = Lang::get('quest.validation');

        // 活動週期
        if (! empty($data['period'])) {
            $data['periodPicker'] = $data['periodPicker'] ? \explode(',', $data['periodPicker']) : null;

            switch ($data['period']) {
                case 'daily':
                    $arr['periodPicker'] = 'required|array|size:1';
                    break;

                default:
                    $arr['periodPicker'] = 'required|array|size:2';
                    break;
            }
        }

        // 驗證活動條件
        if (! empty($data['condition'])) {
            $conditions = json_decode($data['condition'], true);
            $conditions = collect($conditions);

            // 驗證金額，必須為正整數
            // 流水、贈點、轉換百分比需大於零
            $check = $conditions
                ->filter(function ($item) {
                    return (isset($item['num']) && $item['num'] < 0)
                        || (isset($item['amount']) && $item['amount'] <= 0)
                        || (isset($item['reward']) && $item['reward'] <= 0)
                        || (isset($item['percent']) && $item['percent'] <= 0);
                })->count();

            $data['checkNumFormat'] = $check;
            $arr['checkNumFormat']  = 'required|integer|max:0';

            switch ($data['type']) {
                case QuestRewardCombine::TYPE_FIXED:
                    $arr['reward'] = 'required|integer|min:1';

                    // 驗證獲利金額或負利金額
                    $check = $conditions->whereIn('code', static::$reportTotal)->count();

                    if ($check > 1) {
                        $data['checkReportTotal'] = $check;
                        $arr['checkReportTotal']  = 'required|integer|max:1';
                    }

                    // 驗證首儲
                    $check = $conditions->where('code', QuestRewardCombine::CONDITION_FIRST_TIME_DEPOSIT);

                    if (! is_null($check) && $check->count() > 0) {
                        $arr['limitTimes'] = 'required|integer|min:1|max:1';
                    }

                    // 驗證儲值活動
                    $check = $conditions->whereIn('code', [
                        QuestRewardCombine::CONDITION_FIRST_TIME_DEPOSIT,
                        QuestRewardCombine::CONDITION_MULTIPLE_DEPOSIT,
                        QuestRewardCombine::CONDITION_REGULAR_DEPOSIT,
                    ]);

                    if (! is_null($check) && $check->count() > 1) {
                        $data['checkDeposit'] = $check->count();
                        $arr['checkDeposit']  = 'required|integer|max:1';
                    }

                    // 前派的固定型活動，必填有效流水量
                    $check = $conditions->firstWhere('code', 'valid_amount');
                    $check = $check['num'] ?? null;

                    if ($data['method'] === QuestRewardCombine::METHOD_BEFORE) {
                        $data['checkValidAmount'] = $check;
                        $arr['checkValidAmount']  = 'required|numeric|min:0.0001';
                    }
                    break;
                case QuestRewardCombine::TYPE_PERCENT:
                    $arr['percentScale'] = 'required|numeric|min:0.0001';

                    // 驗證獲利金額或負利金額
                    $check = $conditions->whereIn('code', static::$reportTotal)->count();

                    if ($check > 1) {
                        $data['checkReportTotal'] = $check;
                        $arr['checkReportTotal']  = 'required|integer|max:1';
                    }

                    // 驗證首儲
                    $check = $conditions->where('code', QuestRewardCombine::CONDITION_FIRST_TIME_DEPOSIT);

                    if (! is_null($check) && $check->count() > 0) {
                        $arr['limitTimes'] = 'required|integer|min:1|max:1';
                    }

                    // 驗證儲值活動
                    $check = $conditions->whereIn('code', [
                        QuestRewardCombine::CONDITION_FIRST_TIME_DEPOSIT,
                        QuestRewardCombine::CONDITION_MULTIPLE_DEPOSIT,
                        QuestRewardCombine::CONDITION_REGULAR_DEPOSIT,
                        QuestRewardCombine::CONDITION_SINGLE_DAY_FIRST_TIME_DEPOSIT,
                    ]);

                    if (! is_null($check) && $check->count() > 1) {
                        $data['checkDeposit'] = $check->count();
                        $arr['checkDeposit']  = 'required|integer|max:1';
                    }

                    // 前派的百分比型活動，要選一個儲值條件
                    if ($data['method'] === QuestRewardCombine::METHOD_BEFORE) {
                        $data['checkDeposit'] = $check->count();
                        $arr['checkDeposit']  = 'required|integer|min:1|max:1';

                        // 前派的流水倍數
                        $find                     = $conditions->firstWhere('code', 'amount_scale');
                        $data['checkAmountScale'] = $find['num'];
                        $arr['checkAmountScale']  = 'required|numeric|min:0.0001';
                    }

                    // 後派的百分比型活動，必填有效流水量
                    $check = $conditions->firstWhere('code', 'valid_amount');
                    $check = $check['num'] ?? null;

                    if ($data['method'] === QuestRewardCombine::METHOD_AFTER) {
                        $data['checkValidAmount'] = $check;
                        $arr['checkValidAmount']  = 'required|numeric|min:0.0001';
                    }
                    break;

                case QuestRewardCombine::TYPE_STAGES:
                    $countCondition   = 0;
                    $countFormat      = $data['checkNumFormat'];
                    $countAmount      = 0;
                    $countReportTotal = 1;

                    foreach ($conditions as $item) {
                        $conditionOfStep = collect($item['condition']);

                        if ($conditionOfStep->count() < 0) {
                            $countCondition++;
                        }

                        // 轉換比例
                        if ($item['type'] === QuestRewardCombine::TYPE_PERCENT
                            || $data['method'] === QuestRewardCombine::METHOD_BEFORE) {
                            $data['percentScale'] = $item['percent'];
                            $arr['percentScale']  = 'required|numeric|min:0.0001';
                        }

                        $countFormat += $conditionOfStep
                            ->filter(function ($item) {
                                return $item['num'] < 0;
                            })
                            ->count();

                        if ($data['method'] === QuestRewardCombine::METHOD_AFTER) {
                            $countAmount += ($conditionOfStep->where('code', 'valid_amount')->count() === 0)
                            ? 1
                            : 0;
                            $countReportTotal += ($conditionOfStep->whereIn('code', static::$reportTotal)->count() > 1)
                            ? 1
                            : 0;
                        } else {
                            // 前派的流水倍數
                            $find                     = $conditionOfStep->firstWhere('code', 'amount_scale');
                            $data['checkAmountScale'] = $find['num'];
                            $arr['checkAmountScale']  = 'required|numeric|min:0.0001';
                        }
                    }

                    // 驗證每階段的條件
                    if ($countCondition > 0) {
                        $data['checkCondition'] = $countCondition;
                        $arr['checkCondition']  = 'required|lte:0';
                    }

                    // 驗證每階段的數字欄位
                    // 錯誤的筆數，大於零則噴錯
                    if ($countFormat > 0) {
                        $data['checkNumFormat'] = $countFormat;
                        $arr['checkNumFormat']  = 'required|max:0';
                    }

                    // 驗證有效流水
                    if ($countAmount > 0) {
                        $data['checkAmount'] = $countAmount;
                        $arr['checkAmount']  = 'required|lte:0';
                    }

                    // 每階段的獲利負利金額，只能擇一
                    if ($countReportTotal > 0) {
                        $data['checkReportTotal'] = $countReportTotal;
                        $arr['checkReportTotal']  = 'required|max:1';
                    }

                    if ($data['method'] === QuestRewardCombine::METHOD_AFTER) {
                        $conditions = $conditions
                            ->pluck('condition')
                            ->flatten(1);
                        $countProfit = $conditions->where('code', 'profit-report-total')->count();
                        $countLoss   = $conditions->where('code', 'loss-report-total')->count();

                        if ($countProfit > 0 && $countLoss > 0) {
                            $data['singleReportTotal'] = $countProfit + $countLoss;
                            $arr['singleReportTotal']  = 'required|integer|max:1';
                        }
                    }
                    break;
            }
        }

        (new static($data, $arr, $msg))->check();
    }

    public static function editQuest($data)
    {
        $arr = [
            'id'                 => 'required|exists:quest_reward_combine,id',
            'enabled'            => 'required|boolean',
            'name'               => ['required', 'string', 'max:191'],
            'imageSmall'         => 'nullable',
            'imageLarge'         => 'nullable',
            'information'        => 'nullable|string',
            'informationDisplay' => 'required|boolean',
            'endTime'            => 'nullable|date',
            'startTime'          => 'nullable|date',
        ];
        // 如果有套用群組, 就檢查
        if (isset($data['groupId']) && (int) $data['groupId'] !== 0) {
            $arr = [
                'groupId' => 'nullable|exists:quest_reward_combine_groups,id',
            ];
        }
        (new static($data, $arr))->check();
    }

    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id'      => 'required|exists:quest_reward_combine,id',
            'enabled' => 'required|boolean',
        ]))->check();
    }

    public static function checkGetList($data)
    {
        (new static($data, [
            'enabled' => 'nullable|boolean',
            'page'    => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]))->check();
    }

    public static function checkGetLog($data)
    {
        (new static($data, [
            'enabled'  => 'nullable|boolean',
            'page'     => 'nullable|integer|min:1',
            'perPage'  => 'nullable|integer|min:1',
            'memberId' => 'required|integer',
            'questId'  => 'required|integer',
        ]))->check();
    }

    public static function addAnnouncement($data)
    {
        $arr = [
            'franchiseeId'       => 'nullable',
            'agentId'            => 'nullable|string',
            'clubRankId'         => 'nullable|string',
            'memberId'           => 'nullable|string',
            'enabled'            => 'required|boolean',
            'name'               => ['required', 'string', 'max:191'],
            'endTime'            => 'nullable|date',
            'startTime'          => 'nullable|date',
            'imageSmall'         => 'nullable|mimes:jpeg,bmp,png,gif,jpg',
            'information'        => 'nullable|string',
            'informationDisplay' => 'required|boolean',
        ];
        $msg = Lang::get('quest.validation');

        (new static($data, $arr, $msg))->check();
    }

    public static function editAnnouncement($data)
    {
        $arr = [
            'id'                 => 'required|exists:quest_reward_combine,id',
            'franchiseeId'       => 'nullable',
            'agentId'            => 'nullable|string',
            'clubRankId'         => 'nullable|string',
            'memberId'           => 'nullable|string',
            'enabled'            => 'required|boolean',
            'name'               => ['required', 'string', 'max:191'],
            'endTime'            => 'nullable|date',
            'startTime'          => 'nullable|date',
            'imageSmall'         => 'nullable', // 這邊由 service 判斷檔案格式，因為需要回傳字串比對
            'information'        => 'nullable|string',
            'informationDisplay' => 'required|boolean',
        ];
        $msg = Lang::get('quest.validation');

        (new static($data, $arr, $msg))->check();
    }

    public static function checkGetContent($data)
    {
        (new static($data, [
            'id' => 'required|exists:quest_reward_combine,id',
        ]))->check();
    }

    public static function checkTelebotPush($data)
    {
        (new static($data, [
            'id' => 'required|integer|min:1',
        ]))->check();
    }
}
