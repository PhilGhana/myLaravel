<?php

namespace GameProvider\Operator\Params;

class LaunchGameParameter
{
    /**
     * 使用者參數.
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
     * PC mobile.
     * @var string
     */
    public $device = null;

    /**
     * 使否使用https通訊
     * （Y, N）.
     *
     * @var string
     */
    public $isSSL = 'Y';

    /**
     * 群組設定.
     *
     * @var string
     */
    public $group = null;

    /**
     * 使否試玩.
     *
     * @var bool
     */
    public $fun = false;

    /**
     * 遊戲必須要我們先存token驗證時使用.
     *
     * @var string
     */
    public $token = null;

    /**
     * 單一錢包專用，當為true時，改使用token驗證，不使用帳密.
     *
     * @var bool
     */
    public $needToken = false;

    public $lang = 'cn';

    /**
     * 遊戲廠商.
     *
     * @var string
     */
    public $gamehall = null;

    /**
     * windowMode 1: 使用 JDB遊戲大廳 （默認值） ※若未帶入 gType 及 mType ，則直接到遊戲大廳 ，則直接到遊戲大廳 ※若 帶入 gType 及  mType 時，直接進入遊戲。
     * windowMode 2: 不使用 JDB遊戲大廳 遊戲大廳 ※gType 及 mType 為必填字段。
     *
     * 原則上我們不使用大廳
     *
     * @var string
     */
    public $windowMode = 2;
}