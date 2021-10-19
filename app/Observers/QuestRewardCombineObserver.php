<?php

namespace App\Observers;

use App\Models\QuestRewardCombine;

class QuestRewardCombineObserver extends BaseObserver
{
    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var QuestRewardCombine $role */
        $role = $model;

        return $role->name;
    }

    public function updating(\App\Models\BaseModel $model)
    {
        if ($this->anyDirtys($model, ['name', 'enabled'])) {
            parent::updating($model);
        }
    }
}
