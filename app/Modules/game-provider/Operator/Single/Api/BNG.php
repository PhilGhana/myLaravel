<?php

namespace GameProvider\Operator\Single\Api;

use App\Models\LogMemberWallet;
use GameProvider\Operator\BaseApi;

use GameProvider\Operator\Single\Config\BNGConfigConstract;

use GameProvider\Operator\Single\BaseSingleWalletInterface;

use GameProvider\Services\SingleWalletService;

use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\TransferParameter;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\AuthorizeParameter;
use GameProvider\Operator\Params\BalanceParameter;

use GameProvider\Exceptions\AesException;
use GameProvider\Exceptions\LoginException;
use GameProvider\Exceptions\GameListException;
use GameProvider\Exceptions\JSONException;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\CreateMemberException;
use GameProvider\Exceptions\LaunchGameException;
use GameProvider\Exceptions\SyncException;
use GameProvider\Exceptions\TransferException;

use GameProvider\Operator\Feedback\MemberFeedback;
use GameProvider\Operator\Feedback\TransferFeedback;
use GameProvider\Operator\Feedback\BalanceFeedback;
use GameProvider\Operator\Feedback\LaunchGameFeedback;
// use MultiWallet\Feedback\SyncCallBackFeedback;

use App\Models\Report;


class BNG extends BaseApi implements BaseSingleWalletInterface
{
    protected $config;

    function __construct(array $config)
    {
        $this->config                = new BNGConfigConstract();
        $this->config->wl            = $config['WL'];
        $this->config->gameServerUrl = $config['GAME_SERVER_URL'];
        $this->config->projectName   = $config['PROJECT_NAME'];
        $this->config->apiToken      = $config['API_TOKEN'];
    }

    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $config = $this->config;
        $params = [
            "wl=" . ($config->wl),
            "token=" . ($launchGameParams->token),
            "ts=" . (time()),
            "demo=" . ($launchGameParams->fun ?? ''),
            "lang=" . ($launchGameParams->lang ?? ''),
        ];
        $path =  'lobby.html';

        if ($launchGameParams->gameId) {
            $path = 'lobby/game';
            $params[] = "game={$launchGameParams->gameId}";
        }

        $paramStr = implode('&', $params);
        return "{$config->gameServerUrl}/{$config->projectName}/{$path}?{$paramStr}";
    }

    // 不執行，這個平台沒有提供同步
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {

    }

}
