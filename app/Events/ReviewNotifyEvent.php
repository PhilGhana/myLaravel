<?php

namespace App\Events;

use App\Models\View;
use App\Models\Review\BaseReviewModel;

class ReviewNotifyEvent
{

    public $review;

    public function __construct(BaseReviewModel $review)
    {
        $this->review = $review;
    }

}
