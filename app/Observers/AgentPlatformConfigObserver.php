<?php

namespace App\Observers;

use App\Models\AgentPlatformConfig;

class AgentPlatformConfigObserver extends BaseObserver
{

    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var AgentPlatformConfig $config */
        $config = $model;
        $account = $config->agent->account ?? '';
        $gname = $config->gamePlatform->name ?? '';
        return "{$account} - {$gname}";
    }

    protected function getFranchiseeId(\App\Models\BaseModel $model)
    {
        return $model->agent->franchisee_id;
    }
}
