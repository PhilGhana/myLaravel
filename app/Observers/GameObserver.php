<?php

namespace App\Observers;

use App\Models\Game;

class GameObserver extends BaseObserver
{

    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var Game $game */
        $game = $model;

        $name = $game->name ?:
            $game->name_en  ?:
            $game->name_zh_cn ?:
            $game->name_zh_tw ?:
            $game->name_zh_jp;

        return "{$game->code} - {$name}";

    }
}
