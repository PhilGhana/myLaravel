<?php

namespace App\Http\Middleware;

use Illuminate\Session\Middleware\StartSession;

class CustomStartSession extends StartSession
{

    // public function handle($request, \Closure $next)
    // {
    //     if (config('app.session_mode')) {
    //         return parent::handle($request, $next);
    //     }
    //     return $next($request);
    // }
}
