<?php

namespace App\Http\Controllers\Game;

use Illuminate\Routing\Controller as BaseController;
use App\Services\Redis\GameCacheService;

class GameBaseController extends BaseController
{
    const GAME_CACHE = 'game-cache';
    const GAME_CACHE_HASHCODE = 'game-cache-hashCode';

    protected function clearGameCache($fid = 0)
    {
        GameCacheService::clearAll();
    }
}
