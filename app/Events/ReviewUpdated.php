<?php

namespace App\Events;

use App\Models\View;
use App\Models\Review\BaseReviewModel;

class ReviewUpdated
{

    /**
     * 審核單
     *
     * @var BaseReviewModel
     */
    public $review;

    public function __construct (BaseReviewModel $review)
    {
        $this->review = $review;
    }

}
