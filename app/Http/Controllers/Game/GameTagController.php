<?php

namespace App\Http\Controllers\Game;

use App\Models\GameTag;
use App\Models\GameType;
use App\Validators\Game\GameTagValidator;

class GameTagController extends GameBaseController
{
    public function addGameTag()
    {
        $data = request()->all();

        GameTagValidator::checkAdd($data);
        $gameTag = new GameTag();
        $gameTag->tag = $data['tag'];
        $gameTag->name = $data['name'];
        $gameTag->saveOrError();

        // $this->deleteRedis();
    }

    public function editGameTag()
    {
        $data = request()->all();

        GameTagValidator::checkEdit($data);
        $gameTag = GameTag::where('tag', $data['tag'])
            ->first();
        $gameTag->name = $data['name'];
        $gameTag->saveOrError();

        // $this->deleteRedis();
    }

    public function getGameTagList()
    {
        $data = request()->all();

        $query = GameTag::selectRaw('*');

        $tag = $data['tag'] ?? null;
        if ($tag) {
            $query->where('tag', 'like', '%' . $tag . '%');
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
                'content' => $res->map(function ($row) {
                    return [
                        'tag' => $row->tag,
                        'tagName' => $row->name,
                        'updatedAt' => $row->updated_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'page' => $res->currentPage(),
                'perPage' => $res->perPage(),
                'total' => $res->total(),
            ]);
    }

    public function gameTypeOptions()
    {
        $res = GameType::select(['type', 'name'])->get();
        return apiResponse()->data($res);
    }
}