<?php

namespace MultiWallet\Base;

use MultiWallet\Params\MemberParameter;
use MultiWallet\Params\TransferParameter;
use MultiWallet\Params\LaunchGameParameter;
use MultiWallet\Params\SyncReportParameters;

use MultiWallet\Feedback\BalanceFeedback;

interface BaseMultiWalletInterface
{
    /**
     * 獲取遊戲列表
     *
     * @return void
     */
    public function getGameList();

    /**
     * 建立會員
     *
     * @return void
     */
    public function createMember(MemberParameter $member);

    /**
     * 存款
     *
     * @return void
     */
    public function deposit(TransferParameter $transfer);

    /**
     * 提款
     *
     * @return void
     */
    public function withdraw(TransferParameter $transfer);

    /**
     * 會員登入（取得遊戲路徑）
     *
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams);

    /**
     * 取得會員餘額
     *
     * @return void
     */
    public function getBalance(MemberParameter $member);

    /**
     * 同步注單
     *
     * @return void
     */
    public function syncReport(SyncReportParameters $srp, callable $callback);
}
