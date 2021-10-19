<?php

namespace App\Events;

use App\Models\Marquee;

class MarqueeUpdated
{

    /**
     * @var Marquee
     */
    public $marquee;

    public function __construct (Marquee $marquee)
    {
        $this->marquee = $marquee;
    }

}
