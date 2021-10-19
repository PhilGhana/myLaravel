<?php

namespace App\Events;

use App\Models\LetterMessage;

class LetterMessageUpdated
{

    /**
     * @var LetterMessage
     */
    public $letter;

    public function __construct (LetterMessage $letter)
    {
        $this->letter = $letter;
    }

}
