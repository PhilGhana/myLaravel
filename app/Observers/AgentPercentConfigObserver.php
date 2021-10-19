<?php

namespace App\Observers;

use App\Models\AgentPercentConfig;

class AgentPercentConfigObserver extends BaseObserver
{

    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var AgentPercentConfig $config */
        $config = $model;
        $account = $config->agent->account ?? '';
        return "{$account} - " . __('agent.fee-percent-config');
    }

    protected function getFranchiseeId(\App\Models\BaseModel $model)
    {
        return $model->agent->franchisee_id;
    }

}
