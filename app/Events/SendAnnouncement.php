<?php

namespace App\Events;

use App\Models\LetterMessage;

class SendAnnouncement
{

    /**
     * @var array
     */
    public $data = [
        'all' => false,
        'members' => [],
        'agents' => [],
        'franchiseeId' => 0,
        'clubRanks' => [],
        'tags' => [],
        'registerStart' => null,
        'registerEnd' => null,
    ];

    public function setAll()
    {
        $this->data['all'] = true;
    }

    public function setMembers(array $mids)
    {
        $this->data['mids'] =  $mids;
        return $this;
    }

    public function setAgents(array $aids)
    {
        $this->data['agents'] = $aids;
        return $this;
    }

    public function setFranchiseeid(int $fid)
    {
        $this->data['franchiseeId'] = $fid;
        return $this;
    }
    public function setClubRanks(array $crids)
    {
        $this->data['clubRanks'] = $crids;
        return $this;
    }

    public function setTags(array $tags)
    {
        $this->data['tags'] = $tags;
        return $this;
    }

    public function setRegisterRange($start = null, $end = null)
    {
        $this->data['registerStart'] = $start;
        $this->data['registerEnd'] = $end;
        return $this;
    }

    public function toJson () {
    }
}
