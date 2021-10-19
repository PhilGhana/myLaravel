<?php

namespace App\Events;

use App\Models\Member;


class MemberUpdated
{

    /**
     * @var Member
     */
    public $member;

    public function __construct (Member $member)
    {
        $this->member = $member;
    }

}
