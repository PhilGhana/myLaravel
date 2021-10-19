<?php

namespace App\Observers;

use App\Models\ClubRank;

class ClubRankObserver extends BaseObserver
{

    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var ClubRank $rank */
        $rank = $model;
        $clubName = $rank->club->name ?? 'unknown';
        return "{$clubName} / {$rank->name}";
    }
}
