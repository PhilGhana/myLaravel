<?php

namespace App\Providers;

use App\Services\Redis\FranchiseeConfigCacheService;
use App\Services\Redis\SystemConfigCacheService;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        Schema::defaultStringLength(191);

        if (config('app.debug')) {
            // 監聽DB Query
            DB::listen(function ($query) {
                Log::channel('sqllog')->debug('DBQuery', [
                    'request'  => request()->url() ?? 'N/A',
                    'sql'      => $query->sql,
                    'bindings' => $query->bindings,
                    'takes'    => $query->time,
                ]);
            });
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('user', function ($app) {
            // session 模式時，不接受 token 變更 session
            $token = config('app.session_mode') ? null : request()->header('user-token') ?: request()->input('token');

            return new UserServiceProvider($token);
        });

        $this->app->singleton('apiResponse', function ($app) {
            return new ApiResponseServiceProvider();
        });

        $this->app->singleton('sconfig', function ($app) {
            return new SystemConfigCacheService();
        });

        $this->app->singleton('fconfig', function ($app) {
            $user = user()->isLogin() ? user()->model() : null;
            $ruser = $user ? ($user->extend ?: $user) : null;

            return $ruser->franchisee_id
                ? new FranchiseeConfigCacheService($ruser->franchisee_id)
                : null;
        });
    }
}
