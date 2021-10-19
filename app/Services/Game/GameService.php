<?php

namespace App\Services\Game;

use DB;
use App\Models\GameTagMapping;


class GameService
{
    /**
     * 更新遊戲的標籤
     *
     * @param string[] $data['tags'] ?? [] game_tag.tag
     * @return void
     */
    public static function updateTags($ids, $tags)
    {
        GameTagMapping::whereIn('game_id', $ids)->delete();

        if ($tags) {

            $inserts = [];
            $now = date('Y-m-d H:i:s');
            foreach ($ids as $id) {
                foreach ($tags as $tag) {
                    $inserts[] = [
                        'game_id' => $id,
                        'game_tag' => $tag,
                        'created_at' => $now,
                    ];
                }
            }
            GameTagMapping::insert($inserts);
        }
    }

    /**
     * 更新遊戲排序編號
     *
     * @param int $platformId 遊戲平台 id
     * @return void
     */
    public static function updateOrder($platformId)
    {
        DB::update('set @idx = 0');
        DB::update('UPDATE `game` SET `order` = (@idx := @idx + 1) * 10 WHERE platform_id = ? ORDER BY `order`', [$platformId]);
    }
}
