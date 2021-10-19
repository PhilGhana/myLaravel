<?php
namespace App\Listeners;

use App\Events\MultiWalletStuckLog;
use App\Models\LogMultiWalletStuck;

class HandleMultiWalletStuckLog
{
    public function handle(MultiWalletStuckLog $log)
    {
        $logMultiWallet = new LogMultiWalletStuck();

        $logMultiWallet->platform_id = $log->platform_id;
        $logMultiWallet->member_id = $log->member_id;
        $logMultiWallet->type = $log->type;
        $logMultiWallet->error_code = $log->error_code;
        $logMultiWallet->error_message = $log->error_message;
        $logMultiWallet->amount = $log->amount;
        $logMultiWallet->process = $log->process;
        $logMultiWallet->status = $log->status;

        $logMultiWallet->save();
    }
}
