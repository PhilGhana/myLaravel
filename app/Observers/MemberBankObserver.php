<?php

namespace App\Observers;

use App\Models\LogMemberBank;
use App\Models\MemberBank;

class MemberBankObserver extends BaseObserver
{

    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var MemberBank $bank */
        $bank = $model;

        return "【{$bank->bank_name}】{$bank->account} - {$bank->name}";
    }

    public function creating(\App\Models\BaseModel $model)
    {
        /** @var MemberBank $bank */
        $bank = $model;

        if ($this->anyDirtys($model, ['account', 'bank_name', 'branch_name'])) {

            $log = new LogMemberBank();
            $log->franchisee_id = $bank->franchisee_id;
            $log->member_id = $bank->member_id;
            $log->account = $bank->account;
            $log->bank_name = $bank->bank_name;
            $log->branch_name = $bank->branch_name;
            $log->status = LogMemberBank::STATUS_CREATE;
            $log->creator_id = user()->isLogin() ? user()->model()->id : 0;
            $log->saveOrError();
        }

        parent::creating($model);
    }

    public function updating(\App\Models\BaseModel $model)
    {
        /** @var MemberBank $bank */
        $bank = $model;

        if ($this->anyDirtys($model, ['account', 'bank_name', 'branch_name'])) {

            $log = new LogMemberBank();
            $log->franchisee_id = $bank->franchisee_id;
            $log->member_id = $bank->member_id;
            $log->account = $bank->account;
            $log->bank_name = $bank->bank_name;
            $log->branch_name = $bank->branch_name;
            $log->status = LogMemberBank::STATUS_MODIFY;
            $log->creator_id = user()->isLogin() ? user()->model()->id : 0;
            $log->saveOrError();
        }

        return parent::updating($model);
    }

    public function deleting(\App\Models\BaseModel $model, $data = null)
    {
        /** @var MemberBank $bank */
        $bank = $model;

        $data = [
            'name' => $bank->name,
            'account' => $bank->account,
            'bankName' => $bank->bank_name,
            'branchName' => $bank->branch_name,
        ];

        $log = new LogMemberBank();
        $log->franchisee_id = $bank->franchisee_id;
        $log->member_id = $bank->member_id;
        $log->account = $bank->account;
        $log->bank_name = $bank->bank_name;
        $log->branch_name = $bank->branch_name;
        $log->status = LogMemberBank::STATUS_DELETE;
        $log->creator_id = user()->isLogin() ? user()->model()->id : 0;
        $log->saveOrError();

        parent::deleting($model, $data);
    }
}
