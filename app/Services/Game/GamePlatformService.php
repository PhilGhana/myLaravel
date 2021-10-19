<?php

namespace App\Services\Game;

use App\Models\GamePlatform;
use App\Models\GamePlatformLimit;
use App\Models\Member;
use DB;

class GamePlatformService
{
    /**
     * 設定遊戲.
     *
     * @param GamePlatform $game
     * @param int[] $members
     * @return void
     */
    public static function updateLimit(GamePlatform $platform, array $members)
    {
        GamePlatformLimit::where('platform_id', $platform->id)->delete();
        $data = Member::whereIn('id', $members)
            ->get()
            ->map(function ($row) use ($platform) {
                return [
                    'platform_id'    => $platform->id,
                    'member_id'      => $row->id,
                    'member_account' => $row->account,
                ];
            })->toArray();
        GamePlatformLimit::insert($data);
    }

    public static function updateOrder()
    {
        DB::update('set @idx = 0');
        DB::update('UPDATE `game_platform` SET `order` = (@idx := @idx + 1) * 10 ORDER BY `order`');
    }

    /**
     * 更新圖片.
     *
     * @param GamePlatform $platform
     * @param array $data
     * @return GamePlatform $query
     */
    public static function updateImage(GamePlatform $platform, array $data)
    {
        // 平台列表用圖示
        if (isset($data['image'])) {
            $platform->uploadImage($data['image']);
        }

        // 遊戲列表上面的平台ICON
        if (isset($data['icon'])) {
            $platform->uploadIcon($data['icon']);
        }

        // 平台頁面用的背景圖
        if (isset($data['pageBgImg'])) {
            $platform->uploadPageBgImg($data['pageBgImg']);
        }

        // 首頁平台圖片
        if (isset($data['indexImg'])) {
            $platform->uploadIndexImg($data['indexImg']);
        }

        // 標頭下滑選單圖片
        if (isset($data['headerImg'])) {
            $platform->uploadHeaderImg($data['headerImg']);
        }

        return $platform;
    }
}
