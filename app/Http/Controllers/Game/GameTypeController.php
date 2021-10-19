<?php

namespace App\Http\Controllers\Game;

use App\Models\GameType;
use App\Validators\Game\GameTypeValidator;

class GameTypeController extends GameBaseController
{
    public function addGameType()
    {
        $data = request()->all();

        GameTypeValidator::checkAdd($data);
        $gameType = new GameType();
        $gameType->type = $data['type'];
        $gameType->name = $data['name'];
        $gameType->saveOrError();

        // $this->deleteRedis();
    }

    public function editGameType()
    {
        $data = request()->all();

        GameTypeValidator::checkEdit($data);
        $gameType = GameType::findOrError($data['type']);
        $gameType->name = $data['name'];
        $gameType->saveOrError();

        // $this->deleteRedis();
    }

    public function getGameType()
    {
        $data = request()->all();

        $query = GameType::select([
            'type',
            'name',
            'updated_at',
        ]);

        $type = $data['type'] ?? null;
        if ($type) {
            $query->where('type', 'like', "%{$type}%");
        }

        $sorts = $data['sorts'] ?? [];
        if (is_array($sorts)) {
            foreach ($sorts as $value) {
                list($col, $by) = explode(',', $value . ',');
                $col = snake_case($col);
                $query->orderBy($col, $by);
            }
        }

        $perPage = intval($data['perPage'] ?? 15);
        $res = $query->paginate($perPage);

        return apiResponse()
            ->data([
                'content' => $res->items(),
                'page' => $res->currentPage(),
                'perPage' => $res->perPage(),
                'total' => $res->total(),
            ]);
    }
}