<?php

namespace App\Observers;

use App\Models\Franchisee;

class FranchiseeObserver extends BaseObserver
{

    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var Franchisee $franchisee */
        $franchisee = $model;

        return $franchisee->name;
    }

}
