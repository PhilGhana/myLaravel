<?php

namespace App\Events;

use App\Models\View;

class ViewUpdated
{

    public $view;

    public function __construct (View $view)
    {
        $this->view = $view;
    }

}
