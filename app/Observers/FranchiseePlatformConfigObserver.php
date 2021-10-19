<?php

namespace App\Observers;

use App\Models\FranchiseePlatformConfig;

class FranchiseePlatformConfigObserver extends BaseObserver
{

    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var FranchiseePlatformConfig $config */
        $config = $model;
        return $config->platform->name ?? '';
    }
}
