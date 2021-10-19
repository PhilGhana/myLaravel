<?php

namespace App\Http\Controllers\Game;

use ApiGameProvider\Base\BaseSeamless;
use ApiGameProvider\Base\Parameters\GameItem;
use App\Events\GameUpdated;
use App\Exceptions\FailException;
use App\Models\Game;
use App\Models\GamePlatform;
use App\Models\GameTag;
use App\Models\GameType;
use App\Services\Game\GameService;
use App\Validators\Game\GameValidator;
use DB;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameController extends GameBaseController
{
    public function addGame()
    {
        $data = request()->all();

        GameValidator::checkAdd($data);
        $game              = new Game();
        $game->platform_id = $data['platformId'];
        $game->type        = $data['type'];
        $game->code        = $data['code'];
        $game->code_mobile = $data['codeMobile'] ?? '';
        $game->code_report = $data['codeReport'] ?? '';
        $game->name_en     = $data['nameEn'] ?? '';
        $game->name_zh_tw  = $data['nameZhTw'] ?? '';
        $game->name_zh_cn  = $data['nameZhCn'] ?? '';
        $game->name_jp     = $data['nameJp'] ?? '';
        $game->enabled     = $data['enabled'] ?? 0;
        $game->maintain    = $data['maintain'] ?? 0;
        $game->order       = $data['order'] ?? 0;
        $game->remark      = $data['remark'] ?? '';
        $game->game_hall   = $data['gameHall'] ?? '';

        if (isset($data['image'])) {
            $game->uploadImage($data['image']);
        }

        DB::transaction(function () use ($game, $data) {
            $game->saveOrError();
            GameService::updateOrder($game->platform_id);
            GameService::updateTags([$game->id], $data['tags'] ?? []);
            $this->clearGameCache();
        });
    }

    public function editGame()
    {
        $data = request()->all();

        GameValidator::checkEdit($data);
        $game              = Game::findOrError($data['id']);
        $game->type        = $data['type'] ?? '';
        $game->name_en     = $data['nameEn'] ?? '';
        $game->name_zh_tw  = $data['nameZhTw'] ?? '';
        $game->name_zh_cn  = $data['nameZhCn'] ?? '';
        $game->name_jp     = $data['nameJp'] ?? '';
        $game->code        = $data['code'];
        $game->code_mobile = $data['codeMobile'] ?? '';
        $game->code_report = $data['codeReport'] ?? '';
        $game->maintain    = $data['maintain'] ?? 0;
        $game->enabled     = $data['enabled'] ?? 0;
        $game->order       = $data['order'] ?? 0;
        $game->remark      = $data['remark'] ?? '';
        $game->game_hall   = $data['gameHall'] ?? '';

        if (isset($data['image'])) {
            $game->uploadImage($data['image']);
        }
        DB::transaction(function () use ($game, $data) {
            $game->saveOrError();
            GameService::updateOrder($game->platform_id);
            GameService::updateTags([$game->id], $data['tags'] ?? []);
            $this->clearGameCache();
        });
    }

    public function toggleEnable()
    {
        $data = request()->all();

        GameValidator::checkToggleEnable($data);
        $game          = Game::findOrError($data['id']);
        $game->enabled = $data['enabled'];

        DB::transaction(function () use ($game) {
            $game->saveOrError();
            $this->clearGameCache();
        });
    }

    public function toggleMaintain()
    {
        $ids      = request()->input('ids');
        $maintain = request()->input('maintain');

        if (! $ids) {
            throw new FailException(__('game.empty-id'));
        }
        $maintain = intval($maintain) ? 1 : 0;
        DB::transaction(function () use ($ids, $maintain) {
            Game::whereIn('id', $ids)->update(['maintain' => $maintain]);
            // $updated = new GameUpdated(null);
            // $updated->gameIds = $ids;
            $this->clearGameCache();
        });
    }

    public function getGameList()
    {
        $data = request()->all();

        $query = Game::with('gameType')
            ->with([
                'platform' => function (HasOne $hasone) {
                    $hasone->select('id', 'name');
                },
                'gameType' => function (HasOne $hasone) {
                    $hasone->select('type', 'name');
                },
                'tags.tag' => function (HasOne $hasone) {
                    $hasone->select('tag', 'name');
                },
            ]);

        $platformId = intval($data['platformId'] ?? 0);
        if ($platformId) {
            $query->where('platform_id', $platformId);
        }

        $name = $data['name'] ?? null;
        if ($name) {
            $query->where(function ($q) use ($name) {
                $q->where('name_en', 'like', "%{$name}%")
                    ->orWhere('name_zh_tw', 'like', "%{$name}%")
                    ->orWhere('name_zh_cn', 'like', "%{$name}%")
                    ->orWhere('name_jp', 'like', "%{$name}%");
            });
        }

        $type = $data['type'] ?? 0;
        if ($type) {
            $query->where('type', $type);
        }

        $tag = $data['tag'] ?? null;
        if ($tag) {
            $query->whereExists(function ($query) use ($tag) {
                $query->from('game_tag_mapping')
                    ->whereRaw('game.id = game_tag_mapping.game_id')
                    ->where('game_tag', $tag);
            });
        }

        $enabled = intval($data['enabled'] ?? -1);
        if ($enabled === 0 || $enabled === 1) {
            $query->where('enabled', $enabled);
        }

        $maintain = intval($data['maintain'] ?? -1);
        if ($maintain >= 0) {
            $query->where('maintain', $maintain ? 1 : 0);
        }

        $sorts = $data['sorts'] ?? [];
        if (is_array($sorts)) {
            foreach ($sorts as $value) {
                list($col, $by) = explode(',', $value.',');
                $col            = snake_case($col);
                $query->orderBy($col, $by);
            }
        }

        $per = intval($data['perPage'] ?? 15);
        $res = $query->paginate($per);

        return apiResponse()->paginate($query, function (Game $row) {
            $attrs = $row->toArray();

            $attrs['platformId'] = $row->platform_id;
            $attrs['platformKey'] = $row->platform->key ?? 'not found';
            $attrs['platformName'] = $row->platform->name ?? 'not found';
            $attrs['type'] = $row->type;
            $attrs['typeName'] = $row->gameType->name ?? 'not found';
            $attrs['name'] = $row->name;
            $attrs['tags'] = $row->tags->pluck('tag');
            $attrs['imageUrl'] = $row->imageUrl;

            return $attrs;
        });

        return apiResponse()
            ->data([
                'content' => $res->map(function (Game $row) {
                    $gtype = $row->gameType;

                    return [
                        'id'           => $row->id,
                        'platformId'   => $row->platform_id,
                        'platformKey'  => $row->platform->key,
                        'platformName' => $row->platform->name,
                        'type'         => $row->type,
                        'typeName'     => $gtype->name ?? '',
                        'tags'         => $row->tags->map(function ($mapping) {
                            return [
                                'tag'  => $mapping->tag->tag ?? '',
                                'name' => $mapping->tag->name ?? '',
                            ];
                        }),
                        'code'       => $row->code,
                        'codeMobile' => $row->code_mobile,
                        'codeReport' => $row->code_report,
                        'name'       => $row->name ?: $row->name_zh_cn,
                        'nameZhCn'   => $row->name_zh_cn,
                        'nameZhTw'   => $row->name_zh_tw,
                        'nameEn'     => $row->name_en,
                        'nameJp'     => $row->name_jp,
                        'enabled'    => $row->enabled,
                        'maintain'   => $row->maintain,
                        'order'      => $row->order,
                        'remark'     => $row->remark,
                        'imageUrl'   => $row->imageUrl,
                        'gameHall'   => $row->game_hall,
                        'updatedAt'  => $row->updated_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'page'    => $res->currentPage(),
                'perPage' => $res->perPage(),
                'total'   => $res->total(),
            ]);
    }

    public function gameTypeOptions()
    {
        $res = GameType::select(['type', 'name'])->get();

        return apiResponse()->data($res);
    }

    public function getGameOptions()
    {
        $platformId = request()->input('platformId', null);
        $name       = request()->input('gameName', null);

        GameValidator::checkGameAll(['platformId' => $platformId]);

        $query = Game::select('id', 'order')
            ->where('enabled', 1)
            ->where('platform_id', $platformId);

        if ($name) {
            $query->where(function ($q) use ($name) {
                $q->where('name_en', 'like', "%{$name}%")
                    ->orWhere('name_zh_tw', 'like', "%{$name}%")
                    ->orWhere('name_zh_cn', 'like', "%{$name}%")
                    ->orWhere('name_jp', 'like', "%{$name}%");
            });
        }

        switch (app()->getLocale()) {
            case 'zh-tw':
                $query->selectRaw('name_zh_tw as Name');
                break;
            case 'zh-cn':
                $query->selectRaw('name_zh_cn as Name');
                break;
            case 'en':
                $query->selectRaw('name_en as Name');
                break;
            case 'jp':
                $query->selectRaw('name_jp as Name');
                break;
        }

        $games = $query->orderBy('order')->get();

        return apiResponse()->data($games);
    }

    public function getGamePlatformOptions()
    {
        // 排除測試版平台
        $platforms = GamePlatform::select('id', 'name')
            ->where('enabled', 1)
            ->where('fun', 0)
            ->orderBy('order')
            ->get();

        return apiResponse()->data($platforms);
    }

    public function getGameTagOptions()
    {
        $res = GameTag::select(['tag', 'name'])
            ->orderBy('tag')
            ->get();

        return apiResponse()->data($res);
    }

    /**
     * 批次更新維修狀態.
     *
     * @return void
     */
    public function batchToggleMaintain()
    {
        $data = request()->all();
        GameValidator::checkBatchToggleMaintain($data);

        $query = Game::leftJoin('game_tag_mapping', 'game.id', '=', 'game_tag_mapping.game_id');

        $tags = $data['tags'] ?? null;
        if ($tags) {
            $query->whereIn('game_tag', $tags);
        }

        $types = $data['types'] ?? null;
        if ($types) {
            $query->whereIn('type', $types);
        }

        if ($tags || $types) {
            $query->update(['maintain' => $data['maintain']]);
        }
    }

    public function updateGames()
    {
        $id       = request()->input('id');
        $platform = GamePlatform::findOrError($id);

        /** @var BaseSeamless $module */
        $module = $platform->getPlatformModule();

        $games = $module->getGameList();

        if ($games === null) {
            throw new FailException('game.no-game-list-api');
        }

        $ids = collect($games)->pluck('id')->values();

        Game::whereNotIn('code', $ids)->update(['enabled' => 0]);

        $oldGames = Game::where('platform_id', $platform->id)
            ->whereIn('code', $ids->all())
            ->get()
            ->keyBy('code');

        $inserts = [];
        foreach ($games as $game) {

            /** @var Game $old */
            $old = $oldGames[$game->id] ?? null;

            if ($old) {
                $old->image      = $game->zhtw->imageUrl;
                $old->name_en    = $game->en->name;
                $old->name_zh_cn = $game->zhcn->name;
                $old->name_zh_tw = $game->zhtw->name;
                $old->name_jp    = $game->jp->name;
                if ($old->isDirty()) {
                    $old->save();
                }
                continue;
            }
            $type = null;
            switch ($game->type) {
                case GameItem::TYPE_SLOT:
                    $type = GameType::TYPE_SLOT;
                    break;
                default:
                    throw new ErrorException("unknown type > {$game->type}");
            }

            $now       = date('Y-m-d H:i:s');
            $inserts[] = [
                'platform_id' => $platform->id,
                'type'        => $type,
                'code'        => $game->id,
                'code_mobile' => $game->id,
                'name_en'     => $game->en->name,
                'name_jp'     => $game->jp->name,
                'name_zh_cn'  => $game->zhcn->name,
                'name_zh_tw'  => $game->zhtw->name,
                'image'       => $game->zhtw->imageUrl,
                'enabled'     => 0,
                'maintain'    => 0,
                'order'       => 0,
                'updated_at'  => $now,
                'created_at'  => $now,
            ];
        }

        if ($inserts) {
            Game::insert($inserts);
        }
    }
}
