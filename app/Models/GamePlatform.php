<?php

namespace App\Models;

use ApiGameProvider\Base\BaseSeamless;
use App\Exceptions\ErrorException;
use App\Observers\GamePlatformObserver;
use Illuminate\Http\UploadedFile;

/**
 * 遊戲平台.
 * @property int $id 遊戲平台ID
 * @property string $namespace 模組檔案位置
 * @property string $key 遊戲平台縮寫
 * @property string $name 遊戲平台名稱
 * @property string $currency 貨幣類型
 * @property string $member_prefix 會員帳號前綴
 * @property int $use_password 是否使用帳號、密碼介接
 * @property int $fun 是否為測試版(非正式機)
 * @property string $image 圖片名稱
 * @property string $icon Icon 圖片
 * @property string $setting 遊戲平台介接參數(json格式)
 * @property int $enabled 啟用(0:停用、1:啟用)
 * @property int $maintain 是否維護中
 * @property int $platformId 關聯的正式平台id
 * @property string $maintain_crontab 設定例行維修時間(使用 linux crontab 格式)
 * @property int $maintain_minute 例行維修的時間長度 (分鐘)
 * @property int $limit 是否限制可使用的會員(0.不限制, 1.限制並參考 game_platform_limit)
 * @property int $order 排序
 * @property int $sync_report_delay 拉報表的時間, 若為 0 則不需要拉取報表 (min)
 * @property \Illuminate\Support\Carbon $updated_at 修改時間
 * @property \Illuminate\Support\Carbon $created_at 建立時間
 *
 *
 * @property string $imageUrl 圖片實際網址
 * @property string $iconUrl icon實際網址
 */
class GamePlatform extends BaseModel
{
    protected $toCamelCase = true;

    protected $connection = 'mysql';

    protected $table = 'game_platform';

    protected $hidden = ['image'];

    protected $casts = [
        'id'              => 'integer',
        'enabled'         => 'integer',
        'use_password'    => 'integer',
        'fun'             => 'integer',
        'maintain'        => 'integer',
        'platformId'      => 'integer',
        'maintain_length' => 'integer',
        'limit'           => 'integer',
        'order'           => 'integer',
        'sync_report_delay',
        'has_app',
    ];

    public static function boot()
    {
        static::observe(GamePlatformObserver::class);
    }

    /**
     * 是否維護中.
     *
     * @return bool
     */
    public function isMaintain()
    {
        return $this->maintain === 1;
    }

    public function gameType()
    {
        return $this->hasOne(GameType::class, 'type', 'type');
    }

    public function isFun()
    {
        return $this->fun === 1;
    }

    public function getImageUrlAttribute()
    {
        return $this->image
            ? config('app.game_image_url')."/{$this->image}"
            : null;
    }

    public function getIconUrlAttribute()
    {
        return $this->icon
            ? config('app.game_image_url')."/{$this->icon}"
            : null;
    }

    public function getPageBgImgUrlAttribute()
    {
        return $this->page_bg_img
            ? config('app.game_image_url')."/{$this->page_bg_img}"
            : null;
    }

    public function getIndexImgUrlAttribute()
    {
        return $this->index_img
            ? config('app.game_image_url')."/{$this->index_img}"
            : null;
    }

    public function getHeaderImgUrlAttribute()
    {
        return $this->header_img
            ? config('app.game_image_url')."/{$this->header_img}"
            : null;
    }

    public function isLimit()
    {
        return $this->limit === 1;
    }

    public function agentPlatformConfig()
    {
        return $this->belongsTo(AgentPlatformConfig::class, 'id', 'platform_id');
    }

    public function limits()
    {
        return $this->hasMany(GamePlatformLimit::class, 'platform_id', 'id');
    }

    public function games()
    {
        return $this->hasMany(Game::class, 'platform_id', 'id');
    }

    /**
     * 產生平台遊戲的會員帳號
     *
     * @param string $account 會員帳號
     * @return void
     */
    public function generatorMemberUsername(string $account)
    {
        return $this->member_prefix."_{$account}";
    }

    /**
     * 產生平台的 api 介接模組.
     *
     * @return BaseSeamless
     */
    public function getPlatformModule()
    {
        $namespace = $this->namespace;

        return $namespace ? new $namespace(json_decode($this->setting, true)) : null;
    }

    /**
     * 驗證圖片格式.並上傳圖片.
     *
     * @param UploadedFile $file
     *
     * @return $filename
     */
    public function CheckUploadImage(UploadedFile $file)
    {
        $allowExtensions = ['jpeg', 'jpg', 'png', 'gif'];
        $basepath        = config('app.game_image_path');
        $imgExt          = $file->extension();

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

        return $filename;
    }

    /**
     * 上傳 平台列表用圖示.
     *
     * @param UploadedFile $file
     *
     * @return void
     */
    public function uploadImage(UploadedFile $file)
    {
        if (config('app.game_platform.image')) {
            // 檢查圖片比例
            list($targetWidth, $targetHeight)        = getimagesize($file);

            $config_size   = json_decode(config('app.game_platform.image'));
            $config_scale  = $this->getScale($config_size[0], $config_size[1]);
            $scale         = $this->getScale($targetWidth, $targetHeight);

            if ($config_scale != $scale) {
                throw new ErrorException(__('game_platform.image_size_error').' width:'.$config_size[0].', height:'.$config_size[1]);
            }
        }

        $this->image = $this->CheckUploadImage($file);
    }

    /**
     * 上傳 遊戲列表上面的平台ICON.
     *
     * @param UploadedFile $file
     *
     * @return $filename
     */
    public function uploadIcon(UploadedFile $file)
    {
        if (config('app.game_platform.icon')) {
            // 檢查圖片比例
            list($targetWidth, $targetHeight)        = getimagesize($file);

            $config_size   = json_decode(config('app.game_platform.icon'));
            $config_scale  = $this->getScale($config_size[0], $config_size[1]);
            $scale         = $this->getScale($targetWidth, $targetHeight);

            if ($config_scale != $scale) {
                throw new ErrorException(__('game_platform.icon_size_error').' width:'.$config_size[0].', height:'.$config_size[1]);
            }
        }
        $this->icon = $this->CheckUploadImage($file);
    }

    /**
     * 上傳 平台頁面用的背景圖.
     *
     * @param UploadedFile $file
     *
     * @return $filename
     */
    public function uploadPageBgImg(UploadedFile $file)
    {
        if (config('app.game_platform.page_bg_img')) {
            // 檢查圖片比例
            list($targetWidth, $targetHeight)        = getimagesize($file);

            $config_size   = json_decode(config('app.game_platform.page_bg_img'));
            $config_scale  = $this->getScale($config_size[0], $config_size[1]);
            $scale         = $this->getScale($targetWidth, $targetHeight);

            if ($config_scale != $scale) {
                throw new ErrorException(__('game_platform.page_bg_img_size_error').' width:'.$config_size[0].', height:'.$config_size[1]);
            }
        }
        $this->page_bg_img = $this->CheckUploadImage($file);
    }

    /**
     * 上傳 首頁平台圖片.
     *
     * @param UploadedFile $file
     *
     * @return $filename
     */
    public function uploadIndexImg(UploadedFile $file)
    {
        if (config('app.game_platform.index_img')) {
            // 檢查圖片比例
            list($targetWidth, $targetHeight)        = getimagesize($file);

            $config_size   = json_decode(config('app.game_platform.index_img'));
            $config_scale  = $this->getScale($config_size[0], $config_size[1]);
            $scale         = $this->getScale($targetWidth, $targetHeight);

            if ($config_scale != $scale) {
                throw new ErrorException(__('game_platform.index_img_size_error').' width:'.$config_size[0].', height:'.$config_size[1]);
            }
        }
        $this->index_img = $this->CheckUploadImage($file);
    }

    /**
     * 上傳 標頭下滑選單圖片.
     *
     * @param UploadedFile $file
     *
     * @return $filename
     */
    public function uploadHeaderImg(UploadedFile $file)
    {
        if (config('app.game_platform.header_img')) {
            // 檢查圖片比例
            list($targetWidth, $targetHeight)        = getimagesize($file);

            $config_size   = json_decode(config('app.game_platform.header_img'));
            $config_scale  = $this->getScale($config_size[0], $config_size[1]);
            $scale         = $this->getScale($targetWidth, $targetHeight);

            if ($config_scale != $scale) {
                throw new ErrorException(__('game_platform.header_img_size_error').' width:'.$config_size[0].', height:'.$config_size[1]);
            }
        }
        $this->header_img = $this->CheckUploadImage($file);
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