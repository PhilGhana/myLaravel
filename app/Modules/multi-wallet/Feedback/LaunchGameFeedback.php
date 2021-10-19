<?php

namespace MultiWallet\Feedback;

class LaunchGameFeedback extends BaseFeedback
{
    /**
     *  遊戲連結
     *
     * @var string
     */
    public $gameUrl = null;

    /**
     *  手機遊戲連結
     *
     * @var string
     */
    public $mobileGameUrl = null;

    /**
     *  授權碼
     *
     * @var string
     */
    public $token = null;

}
