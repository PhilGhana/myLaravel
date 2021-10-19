<?php

namespace GameProvider\Operator\Single;

use GameProvider\Services\SingleWalletService;
// use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\TransferParameter;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\AuthorizeParameter;
use GameProvider\Operator\Params\BalanceParameter;

use GameProvider\Operator\Feedback\LaunchGameFeedback;
interface BaseSingleWalletInterface
{
    /**
     * 會員登入（取得遊戲路徑）
     *
     * @return LaunchGameFeedback
     */
    public function launchGame(LaunchGameParameter $launchGameParams);

    /**
     * 同步注單
     *
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback);
}
