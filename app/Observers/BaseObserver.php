<?php

namespace App\Observers;

use App\Models\BaseModel;
use App\Models\LogDataModify;
use ReflectionClass;

class BaseObserver
{

    protected function getShortName(BaseModel $model)
    {
        $reflect = new ReflectionClass($model);
        return $reflect->getShortName();
    }

    /**
     * 取得 log 的 name (方便使用者查詢)
     *
     * @param BaseModel $model
     * @param string $action 事件類型( LogDataModify::ACTION_{XXX} )
     * @return void
     */
    protected function getName(BaseModel $model, string $action)
    {
        return '';
    }

    protected function getFranchiseeId(BaseModel $model)
    {
        return $model->franchisee_id ?: 0;
    }

    /**
     * 檢查是否只有變更指定的欄位
     *
     * @param BaseModel $model
     * @param string[] $columns
     * @param boolean $equals true 必需與指定的欄位完全相同, false 時比對是否有不同於指定的欄位變更
     * @return void
     */
    protected function onlyDirtys(BaseModel $model, array $columns, $equals = false)
    {
        $dirtys = array_keys($model->getDirty());

        if ($equals) {

            $intersects = array_intersect($dirtys, $columns);

            return count($intersects) === count($dirtys);
        }

        # 取出其他影響到的欄位
        $diff = array_diff($dirtys, $columns);
        return count($diff) === 0;
    }

    /**
     * 只要有任一欄位存在
     *
     * @param BaseModel $model
     * @param array $columns
     * @param boolean $equals
     * @return void
     */
    protected function anyDirtys(BaseModel $model, array $columns)
    {
        $dirtys = array_keys($model->getDirty());

        $intersects = array_intersect($dirtys, $columns);

        return count($intersects) > 0;
    }

    public function creating(BaseModel $model)
    {
        //
    }
    public function updating(BaseModel $model)
    {
        //

        if (!$model->isDirty()) {
            return;
        }
        $before = $model->getOriginal();
        $after = $model->getDirty();

        $change = [];
        foreach ($after as $attr => $val) {
            $change[$attr] = [
                'before' => $before[$attr] ?? null,
                'after' => $val,
            ];
        }
        $log = new LogDataModify();

        $user = user()->model();

        $log->franchisee_id = $this->getFranchiseeId($model);
        $log->operator_id = $user->id ?? 0;
        $log->name = mb_substr($this->getName($model, LogDataModify::ACTION_UPDATED), 0, 50);
        $log->site = LogDataModify::SITE_AGENT;
        $log->model = $this->getShortName($model);
        $log->model_id = $model->id ?: 0;
        $log->ip = request()->ip();
        $log->action = LogDataModify::ACTION_UPDATED;
        $log->path = request()->path();
        $log->content = [
            LogDataModify::ACTION_UPDATED => $change,
        ];
        $log->saveOrError();
    }

    public function deleting(BaseModel $model, $data = null)
    {

        $user = user()->model();
        $log = new LogDataModify();
        $log->name = mb_substr($this->getName($model, LogDataModify::ACTION_DELETED), 0, 50);
        $log->franchisee_id = $this->getFranchiseeId($model);
        $log->operator_id = $user->id ?? 0;
        $log->site = LogDataModify::SITE_AGENT;
        $log->model = $this->getShortName($model);
        $log->ip = request()->ip();
        $log->action = LogDataModify::ACTION_DELETED;
        $log->path = request()->path();
        $log->content = [
            LogDataModify::ACTION_DELETED => $data ?: $model->toArray(),
        ];
        $log->save();
    }
}
