<?php

namespace App\Http\Middleware;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Services\Role\ApiGroupService;
use App\Services\Role\RoleApiService;
use Closure;

class ApiAuth
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
        if (! user()->isLogin()) {
            throw new UnauthorizedException();
        }

        // 權限檢查
        $user = user()->model();
        $serv = (new ApiGroupService())->isCompany($user->isCompany())->setRole($user->role_id);

        if (! $serv->isAllowed(request()->path())) {
            throw new ForbiddenException(__('agent.api-request-not-allowed'));
        }

        return $next($request);
    }
}