<?php

namespace App\Events;

use App\Models\Agent;

class AgentUpdated
{

    /**
     *
     *
     * @var Agent
     */
    public $agent;

    public function __construct (Agent $agent)
    {
        $this->agent = $agent;
    }

}
