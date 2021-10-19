<?php

namespace App\Models;

use App\Exceptions\ErrorException;
use App\Observers\GameObserver;
use Illuminate\Http\UploadedFile;

/**
 * 遊戲.
 * @property int $id id
 * @property int $platform_id 遊戲平台ID
 * @property string $type 遊戲類型 (game_type.type)
 * @property string $code 遊戲編號(對方站台遊戲編號)
 * @property string $code_mobile 遊戲編號(手機版)
 * @property string $code_report 拉報表的遊戲 code
 * @property string $name 遊戲名稱
 * @property string $name_en 英文名稱
 * @property string $name_zh_tw 繁中名稱
 * @property string $name_zh_cn 簡中名稱
 * @property string $name_jp 日文名稱
 * @property int $enabled 是否啟用(0.停用, 1.啟用)
 * @property int $maintain 是否維護中
 * @property string $image 遊戲圖片路徑
 * @property int $order 排序
 * @property \Illuminate\Support\Carbon $updated_at 最後修改日期
 * @property \Illuminate\Support\Carbon $created_at 最後新增日期
 *
 *
 * @property GameTagMapping[] $tags GameTagMapping Model
 * @property GamePlatform $platform
 * @property string $imageUrl
 */
class Game extends BaseModel
{
    const DEFAULT_REPORT_CODE = '0';

    protected $toCamelCase = true;

    protected $table = 'game';

    protected $hidden = ['image'];

    protected $casts = [
        'id'          => 'integer',
        'platform_id' => 'integer',
        'enabled'     => 'integer',
        'maintain'    => 'integer',
        'limit'       => 'integer',
    ];

    public static function boot()
    {
        static::observe(GameObserver::class);
    }

    /**
     * 是否維護中.
     *
     * @return bool
     */
    public function isMaintain()
    {
        return $this->maintain;
    }

    public function isDisabled()
    {
        return $this->enabled === 0;
    }

    public function getNameAttribute()
    {
        switch (app()->getLocale()) {
            case 'zh-tw':
                return $this->name_zh_tw;
            case 'zh-cn':
                return $this->name_zh_cn;
            case 'en':
                return $this->name_en;
            case 'jp':
                return $this->name_jp;
        }
    }

    public function getImageUrlAttribute()
    {
        return $this->image && (mb_substr($this->image, 0, 4) !== 'http')
            ? config('app.game_image_url')."/{$this->image}"
            : ($this->image ?: null);
    }

    public function uploadImage(UploadedFile $file)
    {
        $allowExtensions = ['jpeg', 'jpg', 'png', 'gif'];
        $basepath        = config('app.game_image_path');
        $imgExt          = $file->extension();

        // 檢查圖片比例
        if (config('app.game.image')) {
            list($targetWidth, $targetHeight)        = getimagesize($file);

            $config_size   = json_decode(config('app.game.image'));
            $config_scale  = $this->getScale($config_size[0], $config_size[1]);
            $scale         = $this->getScale($targetWidth, $targetHeight);

            if ($config_scale != $scale) {
                throw new ErrorException(__('game.image_size_error').' width:'.$config_size[0].', height:'.$config_size[1]);
            }
        }

        // 檢查存放路徑是否正確
        if (! is_dir($basepath)) {
            throw new ErrorException("Error Path > {$basepath}");
        }

        // 檢查檔案是否為圖片類型
        if (! in_array($imgExt, $allowExtensions)) {
            throw new FailException("Not Allowe Extensions > {$imgExt}");
        }

        $filename = $file->hashName();
        if (! $file->move($basepath, $filename)) {
            throw new ErrorException('file upload error');
        }

        $this->image = $filename;
    }

    public function platform()
    {
        return $this->hasOne(GamePlatform::class, 'id', 'platform_id');
    }

    public function gameType()
    {
        return $this->hasOne(GameType::class, 'type', 'type');
    }

    public function tags()
    {
        return $this->hasMany(GameTagMapping::class, 'game_id', 'id');
    }

    /**
     * 計算圖片比例.
     *
     * @param int targetWidth 圖片寬度
     * @param int targetHeight 圖片高度
     *
     * @return float $filename
     */
    public function getScale($targetWidth, $targetHeight)
    {
        return round((float) $targetWidth / (float) $targetHeight, 2);
    }
}