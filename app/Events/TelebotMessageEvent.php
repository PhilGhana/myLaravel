<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class TelebotMessageEvent
{
    use SerializesModels;

    public $members = [];

    public $imgUrl = '';

    public $message = '';

    public $options = [];

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        array $members,
        string $message = '',
        ?string $imgUrl = '',
        array $options = []
    ) {
        $this->members = array_values( array_unique($members) );
        $this->message = trim($message);
        $this->imgUrl  = trim($imgUrl);
        $this->options = $options;
    }

}
