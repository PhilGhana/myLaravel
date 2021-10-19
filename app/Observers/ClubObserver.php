<?php

namespace App\Observers;

use App\Models\Club;

class ClubObserver extends BaseObserver
{

    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var Club $club */
        $club = $model;
        return $club->name;
    }

}
