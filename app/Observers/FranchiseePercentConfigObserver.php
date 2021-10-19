<?php

namespace App\Observers;

class FranchiseePercentConfigObserver extends BaseObserver
{

    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        return __('franchisee.fee-percent-config');
    }
}
