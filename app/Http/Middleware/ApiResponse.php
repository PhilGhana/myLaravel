<?php

namespace App\Http\Middleware;

use App\Exceptions\FailException;
use App\Exceptions\UnauthorizedException;
use Closure;

class ApiResponse
{
    /**
     * 會員錢包相關操作
     *
     * @var array
     */
    protected $memberWalletApi = [
        'edit-money',
        'edit-bonus',
        'give-money',
        'take-back',
        'give-error',
        'take-back-error',
        'edit-reward',
        'agent-edit-deposit',
        'third-party-deposit',
    ];

    /**
     * 鎖定時間
     *
     * @var integer 秒
     */
    protected $timeout = 5;

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
        if ($request->is('api/member/wallet/*')) {
            $user = user()->model();
            foreach ($this->memberWalletApi as $api) {
                if ($request->is('api/member/wallet/' . $api . '*')) {
                    $redisKey = "api:" . $api . ":agent:" . $user->id;

                    if (redis('cache')->exists($redisKey)) {
                        throw new FailException(\trans('wallet.locked-request', ['timeout' => $this->timeout]));
                    }
                    redis('cache')->set($redisKey, 1, 'EX', $this->timeout);
                }
            }
        }

        if ($request->is('api/report/total/excel')) {
            return $next($request);
        }

        if ($request->is('api/member/log-report-deposit-withdraw/excel')) {
            return $next($request);
        }

        $res = $next($request);
        return $res->content() === ''
        ? response('', 204)
        : $res;

    }
}
