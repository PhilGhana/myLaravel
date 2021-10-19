<?php

namespace App\Http\Middleware;

use Closure;

class MaintainIP
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
        $url = $request->url();

        if ($url == 'http://t1ts.i88game.net/api/old/transfer/member') {
            return $next($request);
        }

        if ($url == 'http://t1ts.i88game.net/api/old/transfer/agent') {
            return $next($request);
        }

        if ($url == 'http://t1ts.i88game.net/api/old/transfer/percent') {
            return $next($request);
        }

        if ($url == 'http://t1ts.i88game.net/api/old/transfer/memberPoint') {
            return $next($request);
        }

        if ($url == 'http://t1ts.i88game.net/api/old/transfer/report') {
            return $next($request);
        }

        if ($url == 'http://t1ts.i88game.net/api/old/transfer/pointRecord') {
            return $next($request);
        }

        if (config('app.MAINTAIN_ALL')) {
            $ips = explode(',', config('app.MAINTAIN_IP'));

            $ip = $this->get_client_ip();

            if (! in_array($ip, $ips)) {
                die('系統維護中');
            }
        }

        return $next($request);
    }

    public function get_client_ip()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ipaddress = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }

        return $ipaddress;
    }
}
