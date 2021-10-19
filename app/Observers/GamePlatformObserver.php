<?php

namespace App\Observers;

use App\Models\GamePlatform;

class GamePlatformObserver extends BaseObserver
{

    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var GamePlatform $platform */
        $platform = $model;

        return "{$platform->key} - {$platform->name}";
    }
}
