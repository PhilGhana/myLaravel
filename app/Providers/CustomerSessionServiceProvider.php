<?php

namespace App\Providers;

use App\Http\Middleware\CustomStartSession;
use Illuminate\Session\SessionServiceProvider;

class CustomerSessionServiceProvider extends SessionServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CustomStartSession::class);
        $this->registerSessionManager();
        $this->registerSessionDriver();
    }
}
