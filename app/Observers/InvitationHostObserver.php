<?php

namespace App\Observers;

use App\Models\InvitationHost;

class InvitationHostObserver extends BaseObserver
{
    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var InvitationHost $host */
        $host = $model;

        return "{$host->host} - {$host->remark}";
    }

    public function creating(\App\Models\BaseModel $model)
    {
        parent::creating($model);
    }

    public function updating(\App\Models\BaseModel $model)
    {
        parent::updating($model);
    }

    public function deleting(\App\Models\BaseModel $model, $data = null)
    {
        /** @var InvitationHost $host */
        $host = $model;

        $data = [
            'host'   => $host->host,
            'count'  => $host->count,
            'remark' => $host->remark,
        ];
        parent::deleting($model, $data);
    }
}
