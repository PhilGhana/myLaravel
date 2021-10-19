<?php

namespace App\Observers;

use App\Models\Agent;

class AgentObserver extends BaseObserver
{

    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var Agent $agent */
        $agent = $model;

        return "{$agent->account}【{$agent->name}】";

    }

    public function updating(\App\Models\BaseModel $model)
    {

        # 若只有變更這兩個欄位，代表是登入，不記錄 log
        if ($this->onlyDirtys($model, ['error_count', 'log_login_id'])) {
            return ;
        }
        parent::updating($model);
    }
}
