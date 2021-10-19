<?php

namespace MultiWallet\Feedback;

class SyncResultFeedback extends BaseFeedback
{
    public $total = 0;

    public $num_completes = 0;

    public $num_fails = 0;

    public $fails = '';

    public $message = '';

    public $status = '';

}
