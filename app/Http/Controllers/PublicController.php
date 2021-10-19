<?php

namespace App\Http\Controllers;

use App\Exceptions\FailException;
use App\Exceptions\ForbiddenException;
use App\Models\Agent;
use App\Models\LogAgentLogin;
use App\Services\Agent\AgentTesterService;
use App\Services\Checking\SystemCheckingService;
use App\Services\PersonalService;
use Illuminate\Routing\Controller as BaseController;
use IP2LocationLaravel;

class PublicController extends BaseController
{
    public function init()
    {
        $app_name   = \config('app.name');
        $debug      = \config('app.debug');
        $debug_name = \config('app.DEBUG_NAME');
        $web_name   = ($debug) ? $debug_name : $app_name;

        $user = user()->model();
        $data = user()->isLogin()
        ? (new PersonalService($user))->loginData()
        : null;

        if (! is_null($data)) {
            $data['stgPlatform'] = \config('app.stg_special');
            // 如果再未登入時 user 有資料 前端會報錯
            if (! is_null($data) && $user->isCompany()) {
                $data['reviewWithdrawSingleStep'] = \config('app.REVIEW_WITHDRAW_SINGLE_STEP');
            } else {
                $data['reviewWithdrawSingleStep']  = $user->franchisee->review_withdraw_single_step;
            }

            // "測試帳號"篩選按鈕的顯示
            $agentTester              = (new AgentTesterService())->getTesterId();
            $data['hasExcludeTester'] = ! empty($agentTester) ? 1 : 0;
        }

        return [
            'refresh' => [
                'user'                             => $data,
                'web_name'                         => $web_name,
                'lower_stock'                      => config('app.ORGANIZATION_STOCK_LOWER_ONLY'),
            ],
        ];
    }

    public function login()
    {
        $account  = request()->input('account');
        $password = request()->input('password');
        /** @var Agent $agent */
        $agent = Agent::where('account', $account)->first();

        if (is_null($agent)) {
            throw new FailException(__('agent.not-found-account', ['account' => $account]));
        }

        if ($agent && $agent->error_count >= 5) {
            throw new FailException(__('agent.login-error-count', [
                'count' => $agent->error_count,
            ]));
        }

        $records = IP2LocationLaravel::get(request()->ip());

        $token              = (new PersonalService($agent))->loginData()['token'];
        $log                = new LogAgentLogin();
        $log->franchisee_id = $agent->franchisee_id;
        $log->agent_id      = $agent->id;
        $log->ip            = request()->ip();
        $log->success       = 0;
        $log->token         = $token;
        $log->country_name  = ($records['countryName'] != '-') ? $records['countryName'] : null;
        $log->country_code  = ($records['countryName'] != '-') ? $records['countryCode'] : null;
        $log->city          = ($records['countryName'] != '-') ? $records['cityName'] : null;

        $log->updateUserAgent();

        if (! $agent || ! $agent->checkPassword($password)) {
            if ($agent) {
                $agent->error_count += 1;
                $agent->save();

                $log->message = 'password error';
                $log->saveOrError();
            }
            throw new FailException(__('agentLogin.not-found'));
        }

        if (! $agent->role) {
            $log->message = 'agent role not found';
            $log->saveOrError();

            throw new FailException(__('agentLogin.no-role'));
        }

        if ($agent->isDisabled()) {
            $log->message = 'agent is disabled';
            $log->saveOrError();

            throw new FailException(__('agentLogin.disabled'));
        }

        user()->onLoggedIn($agent);
        if (! user()->checkIP()) {
            user()->logout();
            throw new ForbiddenException(__('agent.ip-not-allowed'));
            $log->message = 'ip-not-allowed';
            $log->saveOrError();
        }

        $log->success = 1;
        $log->saveOrError();

        $agent->log_login_id = $log->id;
        $agent->error_count  = 0;
        $agent->save();

        $data = (new PersonalService($agent))->loginData();

        if (! is_null($data)) {
            $data['stgPlatform'] = \config('app.stg_special');
        }

        return [
            'refresh' => [
                'user' => $data,
            ],
        ];
    }

    public function locale()
    {
        $locale = request()->get('locale', request()->cookie('locale') ?: config('app.locale'));
        request()->cookie('locale', $locale);
        app()->setLocale($locale);

        return ['data' => $locale];
    }

    public function logout()
    {
        user()->logout();
    }

    public function index()
    {
        return file_get_contents(base_path('/public/web/index.html'));
    }

    public function checking()
    {
        if (user()->isLogin()) {
            $res = (new SystemCheckingService())->checkAll();

            return apiResponse()->data($res);
        }

        return [];
    }

    public function assetConfig()
    {
        $socketServer = 'window.SocketServer = '.json_encode([
            'host' => config('app.socket_server.host'),
            'port' => config('app.socket_server.port'),
        ]);
        $sessionMode   = config('app.session_mode') ? 'true' : 'false';
        $locale        = config('app.locale');
        $scriptContent = "{$socketServer}; window.SESSION_MODE = {$sessionMode}; window.locale = \"{$locale}\";";

        return response($scriptContent)->header('Content-Type', 'application/javascript');
    }

    public function autoDailyConfig()
    {
        return [
            'auto_daily'    => config('app.auto_daily'),
            'auto_daily_at' => config('app.auto_daily_at'),
        ];
    }
}
