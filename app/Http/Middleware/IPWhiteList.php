<?php

namespace App\Http\Middleware;
use App\Exceptions\UnauthorizedException;
use Closure;
use App\Exceptions\ForbiddenException;
class IPWhiteList
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!user()->checkIP()) {
            throw new ForbiddenException(__('agent.ip-not-allowed'));
        }

        // if (!user()->role->checkIP()) {
            # 角色 ip 限制
        // }

        return $next($request);
    }
}
