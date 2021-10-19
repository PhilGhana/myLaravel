<?php

namespace App\Events;

use App\Models\Carousel;

class CarouselUpdated
{

    /**
     * @var Carousel
     */
    public $carousel;

    public function __construct (Carousel $carousel)
    {
        $this->carousel = $carousel;
    }

}
