<?php

namespace MultiWallet\Params;

class LaunchGameParameter
{
    /**
     * 使用者參數
     *
     * @var MemberParameter
     */
    public $member = null;

    /**
     * 遊戲編號
     *
     * @var string
     */
    public $gameId = null;

    /**
     * 使用裝置
     *
     * @var string
     */
    public $device = null;

    /**
     * 使否使用https通訊
     * （Y, N）
     *
     * @var string
     */
    public $isSSL = 'Y';

    /**
     * 群組設定
     *
     * @var string
     */
    public $group = null;

    /**
     * 遊戲類型
     *
     * @var string
     */
    public $gameType = null;

    /**
     * AG參數(cagent+序列)
     *
     * @var string
     */
    public $sid = null;
}
