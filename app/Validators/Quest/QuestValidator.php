<?php
namespace App\Validators\Quest;

use App\Models\QuestReward;
use App\Validators\BaseValidator;

class QuestValidator extends BaseValidator
{
    private static $fixedData = [
        'clubRankId' => 'nullable|string',
        'questTypeId' => 'required|integer',
        'questName' => 'required|string|max:191',
        'enabled' => 'required|boolean',
        'reward' => 'required|integer|min:1',
        'betAmount' => 'required|integer|min:1',
        'gamesId' => 'nullable|string',
        'extra' => 'nullable|string',
        'endTime' => 'nullable|date',
        'startTime' => 'nullable|date',
        'image' => 'nullable|image',
    ];

    private static $percentData = [
        'clubRankId' => 'nullable|string',
        'questTypeId' => 'required|integer',
        'questName' => 'required|string|max:191',
        'percent' => 'required|numeric|regex:/^[0]+(\.[0-9][0-9]?)?$/',
        'betAmount' => 'nullable|integer',
        'betAmountMin' => 'required|integer|min:1',
        'rewardMax' => 'required|integer|min:1',
        'gamesId' => 'nullable|string',
        'extra' => 'nullable|string',
        'endTime' => 'nullable|date',
        'startTime' => 'nullable|date',
        'image' => 'nullable|image',
    ];

    public static function addFixedQuest($data)
    {
        $arr = static::$fixedData;
        $arr['franchiseeId'] = 'nullable';
        $arr['groupId'] = 'nullable';
        $arr['questTypeName'] = 'required_without:questTypeId';
        $arr['limitTimes'] = 'nullable|integer|min:1';

        if ($data['questTypeId'] == '1' || $data['questTypeId'] == '9') {
            $arr['extra'] = 'required|string';
        }

        if (static::countClubRankQuest($data)) {
            $arr['allClubRank'] = 'required';
        } else if (!is_null($data['franchiseeId'])) {
            $arr['clubRankId'] = 'required';
        }

        (new static($data, $arr))->check();
    }

    public static function editFixedQuest($data)
    {
        $arr = static::$fixedData;
        $arr['questTypeId'] = 'nullable';
        $arr['id'] = 'required|exists:quest_reward,id';

        if ($data['questTypeId'] == '1' || $data['questTypeId'] == '9') {
            $arr['extra'] = 'required|string';
        }

        if (static::countClubRankQuest($data)) {
            $arr['allClubRank'] = 'required';
        } else if (!is_null($data['franchiseeId'])) {
            $arr['clubRankId'] = 'required';
        }

        (new static($data, $arr))->check();
    }

    public static function addPercentQuest($data)
    {
        $arr = static::$percentData;
        $arr['franchiseeId'] = 'nullable';
        $arr['groupId'] = 'nullable';
        $arr['questTypeName'] = 'required_without:questTypeId';

        if ($data['questTypeId'] == '10') {
            $arr['extra'] = 'required|string';
            $arr['betAmountMin'] = 'nullable';
        }

        if (static::countClubRankQuest($data)) {
            $arr['allClubRank'] = 'required';
        } else if (!is_null($data['franchiseeId'])) {
            $arr['clubRankId'] = 'required';
        }

        (new static($data, $arr))->check();
    }

    public static function editPercentQuest($data)
    {
        $arr = static::$percentData;
        $arr['questTypeId'] = 'nullable';
        $arr['id'] = 'required|exists:quest_reward,id';

        if ($data['questTypeId'] == '10') {
            $arr['extra'] = 'required|string';
            $arr['betAmountMin'] = 'nullable';
        }

        if (static::countClubRankQuest($data)) {
            $arr['allClubRank'] = 'required';
        } else if (!is_null($data['franchiseeId'])) {
            $arr['clubRankId'] = 'required';
        }

        (new static($data, $arr))->check();
    }

    public static function addStageQuest($data)
    {
        $arr = static::$fixedData;
        $arr['franchiseeId'] = 'nullable';
        $arr['groupId'] = 'nullable';
        $arr['questTypeName'] = 'required_without:questTypeId';
        $arr['limitTimes'] = 'nullable|integer|min:1';
        $arr['reward'] = 'nullable';
        $arr['betAmount'] = 'nullable';
        $arr['stages'] = 'required|string';

        if (static::countClubRankQuest($data)) {
            $arr['allClubRank'] = 'required';
        } else if (!is_null($data['franchiseeId'])) {
            $arr['clubRankId'] = 'required';
        }

        (new static($data, $arr))->check();
    }

    public static function editStageQuest($data)
    {
        $arr = static::$fixedData;
        $arr['questTypeId'] = 'nullable';
        $arr['id'] = 'required|exists:quest_reward,id';
        $arr['reward'] = 'nullable';
        $arr['betAmount'] = 'nullable';

        if (static::countClubRankQuest($data)) {
            $arr['allClubRank'] = 'required';
        } else if (!is_null($data['franchiseeId'])) {
            $arr['clubRankId'] = 'required';
        }

        (new static($data, $arr))->check();
    }

    public static function checkToggleEnabled($data)
    {
        (new static($data, [
            'id' => 'required|exists:quest_reward,id',
            'enabled' => 'required|boolean',
        ]))->check();
    }

    public static function checkGetList($data)
    {
        (new static($data, [
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]))->check();
    }

    private static function countClubRankQuest($data)
    {
        $questId = intval($data['id']);
        $franchiseeId = $data['franchiseeId'];
        $groupId = $data['groupId'];

        if ($questId > 0) {
            $reward = QuestReward::find($questId);
            $franchiseeId = $reward->franchisee_id;
            $groupId = $reward->group_id;
        }

        if (is_null($franchiseeId)) {
            return false;
        }

        $checkQuest = QuestReward::where('franchisee_id', $franchiseeId)
            ->where('group_id', $groupId)
            ->where('club_rank_id', '=', 'all')
            ->where('id', '!=', $questId)
            ->get();

        return $checkQuest->count() > 0;
    }

    public static function checkGetLog($data)
    {
        (new static($data, [
            'enabled' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
            'memberId' => 'required|integer',
            'questId' => 'required|integer',
        ]))->check();
    }
}