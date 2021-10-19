<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        $this->mapProviderRoutes();

        $this->mapWebSocketRoutes();

        $this->mapWebhookRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware([
                 'api'
             ])
             ->namespace($this->namespace)
             ->group(base_path('routes/api/api.php'));
    }

    protected function mapProviderRoutes()
    {
        Route::middleware(['partner'])
            ->namespace($this->namespace)
            ->group(base_path('routes/provider/provider.php'));
    }

    protected function mapWebSocketRoutes()
    {
        Route::prefix('ws')
            ->namespace($this->namespace)
            ->group(base_path('routes/websocket/ws.php'));
    }

    protected function mapWebhookRoutes()
    {
        Route::prefix('webhook')
        ->middleware('webhook')
        ->namespace($this->namespace . '\Webhook')
        ->group(base_path('routes/webhook.php'));
    }

}
