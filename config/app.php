<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
     */

    'name'                       => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services your application utilizes. Set this in your ".env" file.
    |
     */

    'env'                        => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
     */

    'debug'                      => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
     */

    'url'                        => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
     */

    'timezone'                   => 'Asia/Taipei',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
     */

    'locale'                     => env('LOCALE', 'zh-tw'),

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
     */

    'fallback_locale'            => 'en',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
     */

    'key'                        => env('APP_KEY'),

    'cipher'                     => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
     */

    'providers'                  => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        // Illuminate\Session\SessionServiceProvider::class,
        App\Providers\CustomerSessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers...
         */
        Ip2location\IP2LocationLaravel\IP2LocationLaravelServiceProvider::class,

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        Jenssegers\Agent\AgentServiceProvider::class,
        Maatwebsite\Excel\ExcelServiceProvider::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
     */

    'aliases'                    => [

        'App'          => Illuminate\Support\Facades\App::class,
        'Artisan'      => Illuminate\Support\Facades\Artisan::class,
        'Auth'         => Illuminate\Support\Facades\Auth::class,
        'Blade'        => Illuminate\Support\Facades\Blade::class,
        'Broadcast'    => Illuminate\Support\Facades\Broadcast::class,
        'Bus'          => Illuminate\Support\Facades\Bus::class,
        'Cache'        => Illuminate\Support\Facades\Cache::class,
        'Config'       => Illuminate\Support\Facades\Config::class,
        'Cookie'       => Illuminate\Support\Facades\Cookie::class,
        'Crypt'        => Illuminate\Support\Facades\Crypt::class,
        'DB'           => Illuminate\Support\Facades\DB::class,
        'Eloquent'     => Illuminate\Database\Eloquent\Model::class,
        'Event'        => Illuminate\Support\Facades\Event::class,
        'File'         => Illuminate\Support\Facades\File::class,
        'Gate'         => Illuminate\Support\Facades\Gate::class,
        'Hash'         => Illuminate\Support\Facades\Hash::class,
        'Lang'         => Illuminate\Support\Facades\Lang::class,
        'Log'          => Illuminate\Support\Facades\Log::class,
        'Mail'         => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password'     => Illuminate\Support\Facades\Password::class,
        'Queue'        => Illuminate\Support\Facades\Queue::class,
        'Redirect'     => Illuminate\Support\Facades\Redirect::class,
        // 'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request'      => Illuminate\Support\Facades\Request::class,
        'Response'     => Illuminate\Support\Facades\Response::class,
        'Route'        => Illuminate\Support\Facades\Route::class,
        'Schema'       => Illuminate\Support\Facades\Schema::class,
        'Session'      => Illuminate\Support\Facades\Session::class,
        'Storage'      => Illuminate\Support\Facades\Storage::class,
        'URL'          => Illuminate\Support\Facades\URL::class,
        'Validator'    => Illuminate\Support\Facades\Validator::class,
        'View'         => Illuminate\Support\Facades\View::class,
        'UserAgent'    => Jenssegers\Agent\Facades\Agent::class,
        'Excel'        => Maatwebsite\Excel\Facades\Excel::class,

    ],

    // 使用 session 模式
    'session_mode'               => env('SESSION_MODE', false),

    'socket_server'              => [
        'host'     => env('SOCKET_SERVER_HOST', '//'),
        'api_host' => env('SOCKET_SERVER_API_HOST', '//'),
        'port'     => env('SOCKET_SERVER_PORT', '8000'),
    ],

    'user_unique'                => env('USER_UNIQUE', true),

    'game_mode'                  => env('GAME_PLATFORM_MODE', 'dev'),

    // log 保留 7 天
    // 'log_max_files' =>　7,

    'provider'                   => [
        'access_lifetime' => env('PROVIDER_ACCESS_LIFETIME', 5),
        'lifetime'        => env('PROVIDER_LIFETIME', 60),
    ],

    'log'                        => [
        'exception' => env('LOG_EXCEPTION', false),
    ],

    'carousel_image_path'        => env('CAROUSEL_IMAGE_PATH'),
    'carousel_image_url'         => env('CAROUSEL_IMAGE_URL'),
    'game_image_path'            => env('GAME_IMAGE_PATH'),
    'game_image_url'             => env('GAME_IMAGE_URL'),
    'coupon_image_path'          => env('COUPON_IMAGE_PATH'),
    'coupon_image_url'           => env('COUPON_IMAGE_URL'),
    'quest_image_path'           => env('QUEST_IMAGE_PATH'),
    'quest_image_url'            => env('QUEST_IMAGE_URL'),
    // 遊戲平台圖片尺寸
    'game_platform'              => [
        'image'          => env('PLATFORM_IMAGE'),
        'icon'           => env('PLATFORM_ICON'),
        'page_bg_img'    => env('PLATFORM_PAGE_BG_IMG'),
        'index_img'      => env('PLATFORM_INDEX_IMG'),
        'header_img'     => env('PLATFORM_HEADER_IMG'),
    ],
    // 遊戲圖片尺寸
    'game'              => [
        'image'          => env('GAME_IMAGE'),
    ],
    // 優惠圖片尺寸
    'quest'=> [
        'image_small'          => env('QUEST_IMAGE_SMALL'),
        'image_large'          => env('QUEST_IMAGE_LARGE'),
    ],
    // 輪撥圖片尺寸
    'carousel'=> [
        'image'          => env('CAROUSEL_IMAGE'),
        'mobile_image'   => env('CAROUSEL_MOBILE_IMAGE'),
    ],
    // 活動錢包圖片尺寸
    'activity_wallet'=> [
        'image'          => env('ACTIVITY_WALLET_IMAGE'),
    ],

    // Fullpay config
    'fullpay'                    => [
        'id'         => env('FULLPAY_ID'),
        'key_path'   => env('FULLPAY_PUBLIC_KEY_PATH'),
        'manage_url' => env('FULLPAY_MANAGE_URL'),
        'server_url' => env('FULLPAY_SERVER_URL'),
    ],

    'member_host'                => env('MEMBER_HOST'),

    'default_promo_host'          => env('DEFAULT_PROMO_HOST'),

    // 系統是否啟用自動發派與相關的設定功能

    'auto_daily'                 => env('AUTO_DAILY'),
    'auto_daily_at'              => env('AUTO_DAILY_AT'),

    // fullpay ip
    'fullpayIps'                 => env('FULLPAY_IPS'),

    // 優惠活動的版本控制
    'quest_version'              => env('QUEST_VERSION', 'combine'),

    // STG特別版
    'stg_special'                => env('STG_SPECIAL', false),

    // 多錢包廠商KEY 逗號區隔
    'multi_wallet_platform'      => explode(',', env('MULTI_WALLET_PLATFORM')),

    // 功能模式 tb,stg
    'FUNCTION_MODE'              => env('FUNCTION_MODE', 'tb'),

    // 是否使紅綠籌碼
    'RED_GREEN_BARGAIN'          => env('RED_GREEN_BARGAIN', false),

    // 派發退水是否鎖定流水
    'LOCK_REPORT_APPROVAL_WATER' => env('LOCK_REPORT_APPROVAL_WATER', false),

    // 派發紅利是否鎖定流水
    'LOCK_REPORT_APPROVAL_BONUS' => env('LOCK_REPORT_APPROVAL_BONUS', false),

    // 多錢包同步開關
    'SYNC_MULTI' => env('SYNC_MULTI', false),

    'MG_SPECIAL' => env('MG_SPECIAL', false),

    // 多錢包版本STG
    'STG_MULTI' => env('STG_MULTI', false),

    // debug模式時頁籤顯示名稱
    'DEBUG_NAME' => env('APP_DEBUG_NAME', 'Laravel_demo'),

    // 公司成員在 審核會員存款流程 是否為單一步驟
    'REVIEW_WITHDRAW_SINGLE_STEP' => env('REVIEW_WITHDRAW_SINGLE_STEP', false),

    // 需要同步組織線的平台
    'AUTO_ORGANIZATION_PLATFORM' => explode(',', env('AUTO_ORGANIZATION_PLATFORM', '')),

    // (須排除)測試帳號
    'exclude_tester' => env('EXCLUDE_TESTER', ''),

    'png_live_feed_key' => env('PNG_LIVE_FEED_KEY', null),

    'LOG_INFO' => env('LOG_INFO', false),

    'LOG_DEBUG' => env('LOG_DEBUG', false),

    'REAL_ACCOUNT_PLATFORMS' => env('REAL_ACCOUNT_PLATFORMS', ''),

    'FRANCHISEE_USE_APP_URL' => env('FRANCHISEE_USE_APP_URL', false),

    'ORGANIZATION_STOCK_LOWER_ONLY' => env('ORGANIZATION_STOCK_LOWER_ONLY', false),

    'MAINTAIN_ALL' => env('MAINTAIN_ALL', false),
    'MAINTAIN_IP'  => env('MAINTAIN_IP', ''),
];
