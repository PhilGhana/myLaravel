<?php

namespace App\Observers;

use App\Models\LogDataModify;
use App\Models\Role;

class RoleObserver extends BaseObserver
{
    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var Role $role */
        $role = $model;

        return $role->name;
    }

    public function updating(\App\Models\BaseModel $model)
    {
        // 只有更新 editor 時不處理
        if ($this->onlyDirtys($model, ['editor'])) {
            return;
        }
        parent::updating($model);
    }

    public function creating(\App\Models\BaseModel $model)
    {
        // 只有更新 editor 時不處理
        if ($this->onlyDirtys($model, ['editor'])) {
            return;
        }

        if (! $model->isDirty()) {
            return;
        }
        $before = $model->getOriginal();
        $after  = $model->getDirty();

        $change = [];
        foreach ($after as $attr => $val) {
            $change[$attr] = [
                'before' => $before[$attr] ?? null,
                'after'  => $val,
            ];
        }

        $user               = user()->model();
        $log                = new LogDataModify();
        $log->franchisee_id = $this->getFranchiseeId($model);
        $log->operator_id   = $user->id ?? 0;
        $log->name          = mb_substr($this->getName($model, LogDataModify::ACTION_CREATED), 0, 50);
        $log->site          = LogDataModify::SITE_AGENT;
        $log->model         = $this->getShortName($model);
        $log->model_id      = $model->id ?: 0;
        $log->ip            = request()->ip();
        $log->action        = LogDataModify::ACTION_CREATED;
        $log->path          = request()->path();
        $log->content       = [
            LogDataModify::ACTION_CREATED => $change,
        ];
        $log->saveOrError();
    }
}
