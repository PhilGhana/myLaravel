<?php

namespace App\Observers;

use App\Models\Bank;

class BankObserver extends BaseObserver
{

    protected function getName(\App\Models\BaseModel $model, string $action)
    {
        /** @var Bank $bank */
        $bank = $model;

        return "【{$bank->bank_name}】{$bank->account} - {$bank->name}";
    }
}
