<?php

namespace App\Events;

use App\Models\Role;

class RoleUpdated
{

    /**
     * @var Role
     */
    public $role;

    public function __construct (Role $role)
    {
        $this->role = $role;
    }

}
