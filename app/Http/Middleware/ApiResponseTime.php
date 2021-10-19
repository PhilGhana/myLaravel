<?php

namespace App\Http\Middleware;

use App\Models\LogResponseTime;
use Closure;

class ApiResponseTime
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        

        return $response;
    }

    public function terminate($request, $response)
    {
        $response_time = (microtime(true) - LARAVEL_START);

        if($response_time >= 0.7)
        {
            $lrt = new LogResponseTime();
            $lrt->run_time = $response_time;
    
            $lrt->saveOrError();
        }
        
        
        return ($response);
    }
}