<?php

namespace App\Providers;

use App\Listeners\EventHandle;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // 'App\Events\*' => [
        //     'App\Listeners\HandleReview',
        //     'App\Listeners\HandleUpdateRedisData',
        //     'App\Listeners\HandleMultiWalletStuckLog',
        //     'App\Listeners\HandleLogout',
        //     'App\Listeners\HandleUpdateRedis',
        //     'App\Listeners\HandleException',
        // ],
    ];

    protected $subscribe = [
        EventHandle::class,
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}