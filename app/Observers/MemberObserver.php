<?php

namespace App\Observers;

use App\Models\LogMemberPhone;
use App\Models\Member;

class MemberObserver extends BaseObserver
{

    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var Member $member */
        $member = $model;

        return "{$member->account}【{$member->name}】";
    }

    public function creating(\App\Models\BaseModel $model)
    {
        /** @var Member $member */
        $member = $model;

        $dirtys = array_keys($model->getDirty());

        # 若有變更電話時，要另外記錄
        if (!empty($dirtys['phone'])) {
            $log = new LogMemberPhone();
            $log->franchisee_id = $member->franchisee_id;
            $log->member_id = $member->id;
            $log->phone = $dirtys['phone'];
            $log->creator_id = user()->isLogin() ? user()->model()->id : 0;
            $log->saveOrError();
        }

        return parent::creating($model);
    }

    public function updating(\App\Models\BaseModel $model)
    {
        /** @var Member $member */
        $member = $model;

        $dirtys = $model->getDirty();

        # 若有變更電話時，要另外記錄
        if (!empty($dirtys['phone'])) {
            $log = new LogMemberPhone();
            $log->franchisee_id = $member->franchisee_id;
            $log->member_id = $member->id;
            $log->phone = $member->phone;
            $log->creator_id = user()->isLogin() ? user()->model()->id : 0;
            $log->saveOrError();
        }

        return parent::updating($model);
    }
}
