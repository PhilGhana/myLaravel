<?php

namespace App\Events;

use App\Models\Role;

class RoleApiUpdated
{

    /**
     * @var int
     */
    public $roleId;

    public function __construct (int $roleId)
    {
        $this->roleId = $roleId;
    }

}
