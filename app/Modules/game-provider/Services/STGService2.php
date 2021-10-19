<?php

// namespace GameProvider\Services;

// use App\Events\ThrowException;
// use App\Exceptions\ErrorException;
// use App\Models\Member;
// use App\Models\Report;
// use App\Models\GamePlatform;

// use App\Exceptions\FailException;
// use App\Jobs\STGJob;
// use App\Models\AgentPlatformConfig;
// use App\Models\ClubRankConfig;
// use App\Models\FranchiseePlatformConfig;
// use App\Models\LogMemberResetError;
// use App\Models\LogMemberWallet;
// use App\Models\LogProviderActionRecord;
// use App\Models\LogProviderTransaction;
// use App\Models\LogQuery;
// use App\Models\LogSyncReport;
// use App\Models\MemberPlatformActive;
// use App\Models\MemberWallet;
// use App\Models\ReportCommission;
// use Exception;

// use GameProvider\Operator\Params\SyncCallBackParameter;
// use GameProvider\Operator\Single\BaseSingleWalletInterface;
// use GameProvider\Exceptions\FundsExceedException;
// use GameProvider\Exceptions\SaveFailedException;
// use GameProvider\Operator\Feedback\SyncResultFeedback;
// use GameProvider\Operator\Params\SyncReportParameter;
// use Illuminate\Database\Eloquent\Relations\HasOne;
// use GameProvider\Operator\Single\Api\STG;
// use DB;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Storage;
// use Carbon\Carbon;

// class STGService extends BaseWalletService
// {
//     /**
//      * 轉接環
//      *
//      * @var BaseSingleWalletInterface
//      */
//     protected $api;

//     function __construct(array $config)
//     {
//         $this->platform = GamePlatform::where('key', 'MG')->first();
//         $this->api      = new STG($config);
//     }

//     public function getAction()
//     {
//         return $this->input('function');
//     }

//     /**
//      * 執行動作（路由）
//      *
//      * @return void
//      */
//     public function action()
//     {
//         $action = $this->input('function');
//         switch($action)
//         {
//             // case 'login':
//             //     return $this->login();
//             //     break;
//             case 'doMemberBet':
//                 $this->doCheckRequestId();
//                 return $this->bet();
//                 break;
//             case 'doCancelOrder':
//                 $this->doCheckRequestId('lastrequstid', '-cancelOrder');
//                 return $this->cancelOrder();
//                 break;
//             case 'getBalance':
//                 return $this->getBalance();
//                 break;
//             case 'doOrderSettle':
//                 $this->doCheckRequestId();
//                 return $this->settle();
//                 break;
//             case 'doOrderRESettle':
//                 $this->doCheckRequestId('lastRequestid', '-resettle');
//                 return $this->reSettle();
//                 break;
//             case 'doSendGameResult':
//                 return $this->doSendGameResult();
//                 break;

//             default:
//                 throw new Exception('STG do not have ' . $action . ' action !');
//         }

//     }

//     private function doCheckRequestId($paramName = 'requestid', $post_string = '')
//     {
//         // 存不進去直接報錯 代表重複進單
//         $lpar = new LogProviderActionRecord();
//         $lpar->platform_id = $this->platform->id;
//         $lpar->request_id = $this->input($paramName) . $post_string;

//         if(!$lpar->save()){
//             throw new Exception('STG repeat request id: ' . $this->input($paramName) . ' !');
//         }
//     }

//     // public function login()
//     // {
//     //     $mem           = $this->input('mem');
//     //     $password      = $this->input('password');
//     //     $ip            = $this->input('ip');

 

//     //     $member = user()->model();

//     //     return json_encode([
//     //         'errorcode' => 0,
//     //         'errormsg'  => '成功',
//     //         'mem'       => $mem,
//     //         'balance'   => $member->wallet->money
//     //     ]);
//     // }

//     public function bet()
//     {
//         $gameId    = 'MG';
//         $mem       = $this->input('mem'); // 會員帳號
//         $gtype     = $this->input('gtype'); // 球種
//         $wtype     = $this->input('wtype'); // 玩法
//         $type      = $this->input('type'); // 主客隊，H表示主隊，C表示客隊
//         $gid       = $this->input('gid'); // 賽事編號
//         $requestid = $this->input('requestid'); // 請求ID	
//         $gold      = $this->input('gold'); // 下注金額
//         $orderIP   = $this->input('orderIP'); // 下注IP
//         $orderid   = $this->input('orderid'); // 注單ID

//         // 以下為明細
//         $content = [
//             'stime'           => $this->input('stime'), // 開賽時間
//             'ht'              => $this->input('ht'), // 主隊
//             'ct'              => $this->input('ct'), // 客隊
//             'profit'          => $this->input('profit'), // 獲利%數
//             'profit_original' => $this->input('profit_original'), // 獲利%數
//             'lid'             => $this->input('lid'), // 聯盟
//             'fee'             => $this->input('fee'), // 手續費
//             'lid2'            => $this->input('lid2') // 半場才會有資料 ex:上半
//         ];

//         // $start = microtime(true);
//         $active   = $this->getPlatformActive($mem);
//         // $ac_time = (microtime(true) - $start);
//         // $start = microtime(true);
//         $member   = Member::findOrError($active->member_id);
//         // $member_time = (microtime(true) - $start);

//         // $start = microtime(true);
//         if($this->checkProviderTransaction($requestid) === true)
//         {

//             // 有處理過，跳過
//             return response([
//                 'errorcode' => 0,
//                 'errormsg'  => '成功',
//                 'member'    => $mem,
//                 'balance'   => $member->wallet->money
//             ]);
//         }
//         // $check_time = (microtime(true) - $start);

//         $format = 'Y-m-d H:i:s';
//         $now   = date($format);

//         $parameter               = new SyncCallBackParameter();
//         $parameter->mid          = $orderid;
//         $parameter->uid          = $requestid;
//         $parameter->username     = $mem;
//         $parameter->betAmount    = floatval($gold);
//         $parameter->validAmount  = 0;
//         $parameter->winAmount    = 0;
//         $parameter->gameCode     = $gameId;
//         $parameter->reportAt     = $now;
//         $parameter->betAt        = $now;
//         $parameter->ip           = $orderIP;
//         $parameter->table        = $gid;
//         $parameter->round        = $type;
//         $parameter->content      = json_encode($content);
//         $parameter->status       = Report::STATUS_BETTING;

//         $platform = $this->platform;
        
//         // $start = microtime(true);
//         $report = Report::where('mid', $orderid)
//                 ->where('platform_id', $platform->id)
//                 ->first();
//         // $report_time =  (microtime(true) - $start);

//         $game = $this->getGames($gameId, static::GAME_OPTION_FIRST);

//         // 查錢夠不夠
//         // $start = microtime(true);
//         $wallet = $this->getWallet($active->member_id);
//         if ($wallet->money < $parameter->betAmount)
//         {
//             throw new FundsExceedException(__('provider.found-exceed'));
//         }
//         // $wallet_time = (microtime(true) - $start);

//         $this->checkMemberBetPermission($member);

//         $detail = $report ? $report->detail : null;
//         // $start = microtime(true);
//         [$report, $detail] = $this->generateReport($active, $game, $report, $parameter);
//         // $gen_time = (microtime(true) - $start);

//         $log = new LogMemberWallet();

//         // $start = microtime(true);
//         $this->doWalletTransaction($requestid, $report, $detail, $log, LogMemberWallet::TYPE_BET, false);
//         // $trans_time = (microtime(true) - $start);

//         // $all_time = $ac_time + $member_time + $check_time + $report_time + $wallet_time + $gen_time + $trans_time;
//         // Storage::append('file.log', 'bet record : ' . $orderid . ' all:' . $all_time . ' active:' . $ac_time . ' member:' . $member_time . ' check:' . $check_time . ' report:' . $report_time . ' wallet:'.$wallet_time . ' gen:'.$gen_time.' trans: '.$trans_time);
//         // Log::notice('bet record : ' . $orderid . ' active:' . $ac_time . ' member:' . $member_time . ' check:' . $check_time . ' report:' . $report_time . ' wallet:'.$wallet_time . ' gen:'.$gen_time.' trans: '.$trans_time);

//         return json_encode([
//             'errorcode' => 0,
//             'errormsg'  => '成功',
//             'member'    => $mem,
//             'balance'   => $member->wallet->money
//         ]);
//     }

//     public function cancelOrder()
//     {
//         $mem          = $this->input('mem'); // 會員帳號
//         $lastrequstid = $this->input('lastrequstid'); // 請求ID
//         $orderid      = $this->input('orderid'); // 注單ID

//         $platform = $this->platform;
//         // 回傳過來的帳號竟然有可能是錯的，神啊！太離奇了吧！ 直接判斷單號撤單
//         // $active   = $this->getPlatformActive($mem);

//         // 如果沒有處理過這個
//         if($this->checkProviderTransaction($lastrequstid) !== true)
//         {
//             // 因為本來就沒有這筆支出，所以沒什麼好處理的，直接返還對方需要的
//             return response([
//                 'errorcode' => 0,
//                 'errormsg'  => '成功'
//             ]);
//         }

//         $logWallet = $this->logProvider->log;

//         // 檢查是不是開獎後取消單
//         $report = Report::findOrError($logWallet->type_id);

//         if($report->status === Report::STATUS_COMPLETED)
//         {
//             // 開獎後取消
//             return $this->cancelOrderComplete($report, $logWallet, $lastrequstid);
//         }

//         // 正常取消

//         // 注單取消，退錢
//         $rlog            = new LogMemberWallet();
//         $rlog->member_id = $logWallet->member_id;
//         $rlog->type      = LogMemberWallet::TYPE_ROLLBACK;
//         $rlog->type_id   = $logWallet->id;

//         // $report = Report::findOrError($logWallet->type_id);

//         // if($report->status == Report::STATUS_CANCEL)
//         // {
//         //     // 早就取消過了，不處理
//         //     return response([
//         //         'errorcode' => 0,
//         //         'errormsg'  => '成功'
//         //     ]);
//         // }

//         // // 如果那次操作是投注，把錢加回來
//         // $report->bet_amount += $logWallet->change_money;
//         // $rlog->change_money = $report->total;

//         // # 若只有一筆子單, 把主單狀態改為 rollback
//         // // if ($report->bet_amount === 0)
//         // // {
//         // //     $report->status = Report::STATUS_ROLLBACK;
//         // // }
//         // $report->status = Report::STATUS_CANCEL;
//         $newRequestId = $lastrequstid . '-cancelOrder2';

//         return DB::transaction(function () use ($logWallet, $rlog, $newRequestId) {

//             // 遊戲商竟然會連發，還同分同秒，更換檢查方式
//             // 更新一個狀態是投注中的
//             $update_report_count = Report::where('id', $logWallet->type_id)
//                 ->where('status', '!=', Report::STATUS_CANCEL)
//                 ->update([
//                     'bet_amount' => DB::raw('bet_amount + ' . $logWallet->change_money),
//                     'status' => Report::STATUS_CANCEL
//             ]);

//             // 如果沒有任何一筆被更新，就是做過了
//             if($update_report_count === 0)
//             {
//                 return response([
//                     'errorcode' => 1,
//                     'errormsg'  => '已做過'
//                 ]);
//             }

//             $report = Report::findOrError($logWallet->type_id);

//             $rlog->change_money = $report->updateTotal();

//             $this->logTransaction($this->logProvider->report_id, $newRequestId, $rlog);

//             return response([
//                 'errorcode' => 0,
//                 'errormsg'  => '成功'
//             ]);
//         });
//     }

//     public function cancelOrderComplete(Report $report, $logWallet, $lastrequstid)
//     {
//         // 獲取要追回的金額 只追繳輸贏的部分 本金要退還 所以取會員總計
//         // 這地方要取會員總計回來 會員總計大於0 需要追回 會員總計等於0不需要追發
//         // 真正多派的是扣完退庸的會員總計
//         // 以上追法為STG特例, 其他請勿參考
//         $total = $report->total;

//         $newRequestId = $lastrequstid . '-cancelOrderC';

//         return DB::transaction(function () use ($logWallet, $total, $newRequestId) {

//             // 修改注單狀態
//             $update_report_count = Report::where('id', $logWallet->type_id)
//                 ->where('status', Report::STATUS_COMPLETED)
//                 ->update([
//                     'bet_amount'   => 0,
//                     'valid_amount' => 0,
//                     'win_amount'   => 0,
//                     'status'       => Report::STATUS_CANCEL
//             ]);

//             // 如果沒有任何一筆被更新，就是做過了
//             if($update_report_count === 0)
//             {
//                 return response([
//                     'errorcode' => 1,
//                     'errormsg'  => '已做過'
//                 ]);
//             }

//             // 重拉注單新狀態 重算
//             $report = Report::findOrError($logWallet->type_id);
//             $report->updateTotal();

//             if($total > 0)
//             {
//                 $member_id = $report->member_id;
//                 $report_id = $report->id;
//                 // 這裡回追金額
//                 $this->doTractionMoney($newRequestId, $member_id, $report_id, $total);

//                 // 移除退庸資訊
//                 ReportCommission::where('id', $report_id)->delete();
//             }

//             return response([
//                 'errorcode' => 0,
//                 'errormsg'  => '成功'
//             ]);
//         });
//     }

//     public function getBalance()
//     {
//         $playerId  = $this->input('mem');

//         $active = $this->getPlatformActive($playerId);

//         $wallet = MemberWallet::findOrError($active->member_id);

//         return json_encode([
//             'errorcode' => 0,
//             'errormsg'  => '成功',
//             'member'    => $playerId,
//             'balance'   => $wallet->money
//         ]);
//     }

//     public function settle($cal_bet = true, $dataAry = null, $requestidStr = '')
//     {
//         // DB::connection("mysql")->enableQueryLog();
//         // DB::connection("write")->enableQueryLog();
//         // DB::connection("log")->enableQueryLog();

//         $requestid = '';
//         if($requestidStr == '')
//         {
//             $requestid = $this->input('requestid');
//         }else{
//             $requestid = $requestidStr;
//         }

//         // 支援重算
//         $data = [];
//         $data2 = [];
//         if($dataAry == null)
//         {
//             $data      = $this->input('data');
//             $data2      = $this->input('data2');
//         }else{
//             $data = $dataAry;
//         }

//         $format = 'Y-m-d H:i:s';
//         $time   = date($format);
        

//         $result = [];
//         $gid = $data2['gid'];
//         $orderdate = '';
//         $hr = $data2['teamh_score'];
//         $cr = $data2['teamc_score'];

//         foreach($data as $row)
//         {
//             $gid = $row['gid'];
//             $orderdate = $row['orderdate'];
//             $hr           = $row['hr']; // 主隊分數
//             $cr           = $row['cr']; // 客隊分數
//             $param = $this->makeSyncCallBackParameter($row, $time);
//             if($param != null)
//             {
//                 $result[] = $param;
//             }
//         }

//         $job = new STGJob('doOrderSettle', $requestid, $result, $gid, $orderdate, $cr, $hr);

//         dispatch($job);

//         // 要把單子退回來(暫時) 不再需要
//         // Report::whereHas('detail', function($query) use ($gid) {
//         //     $query->where('table', '=', $gid);
//         // })->where('platform_id', $this->platform->id)
//         // ->update(['status' => Report::STATUS_BETTING]);

//         // $srp = new SyncReportParameter();
//         // $srp->startAt = $time;
//         // $srp->endAt = $time;

//         // $this->doSyncReportsSTG($result, $srp, $requestid, $cal_bet);

//         // // 要自己再對輸的單
//         // $this->doSettleNotWin($gid, $cal_bet, $time, $orderdate, $cr, $hr);

//         // // 寫入遊戲場次 避免到時候無法重新計算
//         // LogProviderActionRecord::where('request_id', $requestid)
//         //     ->update([
//         //         'gid' => $gid
//         //     ]);

//         // $mysql_queries = DB::connection('mysql')->getQueryLog();
//         // $write_queries = DB::connection('write')->getQueryLog();
//         // $log_queries = DB::connection('log')->getQueryLog();

//         // $mysql_data = $this->genQueryData('mysql', $mysql_queries);
//         // $write_data = $this->genQueryData('write', $write_queries);
//         // $log_data = $this->genQueryData('log', $log_queries);
//         // // 所有query寫入
//         // LogQuery::insert($mysql_data);
//         // LogQuery::insert($write_data);
//         // LogQuery::insert($log_data);


//         // $sqlReport = Report::with(array('detail' => function($query) use ($gid) {
//         //     $query->where('table', '=', $gid);
//         // }))->where('platform_id', $this->platform->id)
//         // ->where('status', Report::STATUS_BETTING);

//         // 這樣才不會抓到不是該場的注單
//         // $sqlReport = Report::whereHas('detail', function($query) use ($gid) {
//         //     $query->where('table', '=', $gid);
//         // })->where('platform_id', $this->platform->id)
//         // ->where('status', Report::STATUS_BETTING);

//         // // 輸的單也要算流水
//         // $reports = $sqlReport->get();

//         // foreach($reports as $report)
//         // {
//         //     $report->status = Report::STATUS_COMPLETED;

//         //     if($cal_bet === true)
//         //     {
//         //         $this->calBetAmount($report->member, $report, $this->platform);
//         //     }   
//         // }
        
//         // // 因為只回傳贏的，所以其他輸都改成注單完成
//         // $sqlReport->update(array('status' => Report::STATUS_COMPLETED));

//         return response([
//             'errorcode' => 0,
//             'errormsg'  => '成功'
//         ]);
//     }

//     // 待定
//     public function waitSettle($gid, $requstid)
//     {
//         // 這裡要動作的只有已結算的單
//         $reports = Report::whereHas('detail', function($query) use ($gid) {
//                 $query->where('table', '=', $gid);
//             })->where('platform_id', $this->platform->id)
//             ->where('status', Report::STATUS_COMPLETED)
//             ->get();

//         // 待定專用
//         $newRequestId = $requstid . '-wait';

//         // 要依據不同的注單狀態做動作
//         foreach($reports as $report)
//         {
//             // 必須追繳所有的錢回來 並且將狀態調整為投注中
//             // 追繳的金額為中獎金額 (非有效投注的原因是 : 實際派出(中獎金額) = 投注 + 有效投注 - 退庸)
//             $win_amount = $report->win_amount;
//             $member_id  = $report->member_id;
//             $report_id  = $report->id;

//             try{

//                 DB::beginTransaction();
//                 // 追繳贏的單就可以了
//                 if($win_amount != 0)
//                 {
//                     $rlog               = new LogMemberWallet();
//                     $rlog->member_id    = $member_id;
//                     $rlog->type         = LogMemberWallet::TYPE_ROLLBACK;
//                     $rlog->type_id      = $report_id;
//                     $rlog->change_money = $win_amount * -1;

//                     try {
//                         $this->logTransaction($report_id, $newRequestId, $rlog);
//                     } catch (\Exception $e) {
//                         // 如果發生錢扣不下去,可能是會員沒錢,要記錄起來
//                         $this->doRecordResetMoneyError($member_id, $rlog->change_money, $report_id, $newRequestId);
            
//                     } catch (\Throwable $e) {
//                         // 如果發生錢扣不下去,可能是會員沒錢,要記錄起來
//                         $this->doRecordResetMoneyError($member_id, $rlog->change_money, $report_id, $newRequestId);
//                     }
//                 }

//                 // 必須移除退庸的紀錄
//                 ReportCommission::where('id', $report_id)->delete();

//                 // 輸或贏的單 都要變更狀態為投注中 為下次正確開獎做準備
//                 $report->status       = Report::STATUS_BETTING;
//                 $report->valid_amount = 0;
//                 $report->win_amount   = 0;

//                 // 重新計算 這個步驟就會儲存了 不用擔心
//                 $report->updateTotal();

//                 DB::commit();
//             }catch (\Exception $e) {
//                 DB::rollback();
//             } catch (\Throwable $e) {
//                 DB::rollback();
//             }
//         }
//     }

//     public function cancelSettle($gid, $requstid)
//     {
//         // 取消賽事有兩種情況 一個是已結算 另一個是未結算
//         $reports = Report::whereHas('detail', function($query) use ($gid) {
//                 $query->where('table', '=', $gid);
//             })->where('platform_id', $this->platform->id)
//             ->where('status', '!=', Report::STATUS_CANCEL)
//             ->get();

//         $newRequestId = $requstid . '-cancelSettle';

//         foreach($reports as $report)
//         {
//             $status     = $report->status;
//             $member_id  = $report->member_id;
//             $report_id  = $report->id;

//             if($status == Report::STATUS_BETTING)
//             {
//                 // 要退注額
//                 $bet_amount = $report->bet_amount;
                
//                 $this->doTractionMoney($newRequestId, $member_id, $report_id, $bet_amount);
//             }

//             if($status == Report::STATUS_COMPLETED)
//             {
//                 // 已結算 這時候看也是要退錢 但是贏的話 要追回金額

//                 $total  = $report->total;

//                 if($total > 0)
//                 {
//                     // 贏錢   退回金額  = 會員小計(贏走的錢)
//                     $amount = $total * -1;
//                     $this->doTractionMoney($newRequestId, $member_id, $report_id, $amount);
//                 }else{
//                     // 輸錢  應退回金額就等於投注額
//                     $amount = $report->bet_amount;
//                     $this->doTractionMoney($newRequestId, $member_id, $report_id, $amount);
//                 } 
//             }

//             // 移除退庸資訊
//             ReportCommission::where('id', $report_id)->delete();

//             // 不論怎麼退的 都要把它變成取消單
//             $report->status       = Report::STATUS_CANCEL;
//             $report->bet_amount   = 0;
//             $report->valid_amount = 0;
//             $report->win_amount   = 0;

//             $report->updateTotal();
//         }
//     }

//     private function doTractionMoney($newRequestId, $member_id, $report_id, $amount)
//     {
//         $rlog               = new LogMemberWallet();
//         $rlog->member_id    = $member_id;
//         $rlog->type         = LogMemberWallet::TYPE_ROLLBACK;
//         $rlog->type_id      = $report_id;
//         $rlog->change_money = $amount;

//         try {
//             $this->logTransaction($report_id, $newRequestId, $rlog);
//         } catch (\Exception $e) {
//             // 如果發生錢扣不下去,可能是會員沒錢,要記錄起來
//             $this->doRecordResetMoneyError($member_id, $rlog->change_money, $report_id, $newRequestId);

//         } catch (\Throwable $e) {
//             // 如果發生錢扣不下去,可能是會員沒錢,要記錄起來
//             $this->doRecordResetMoneyError($member_id, $rlog->change_money, $report_id, $newRequestId);
//         }
//     }

//     private function genQueryData($type, $queries)
//     {
//         $now = Carbon::now()->toDateTimeString();
//         $data = array();
//         foreach($queries as $query)
//         {
//             $data[] = [
//                 'type' => $type,
//                 'query' => $query['query'],
//                 'binds' => json_encode($query['bindings']),
//                 'time' => $query['time'],
//                 'updated_at' => $now,
//                 'created_at' => $now
//             ];
//         }
//         return $data;
//     }

//     public function doSendGameResult()
//     {
//         $data        = $this->input('data');
//         $gid         = $data['gid'];
//         $teamc_score = $data['teamc_score'];
//         $teamh_score = $data['teamh_score'];

//         $format = 'Y-m-d H:i:s';
//         $time   = date($format);

//         $this->doSettleNotWin($gid, true, $time, null, $teamc_score, $teamh_score);

//         return response([
//             'errorcode' => 0,
//             'errormsg'  => '成功'
//         ]);
//     }

//     public function doSettleNotWin($gid, $cal_bet, $settleAt = null, $gameAt = null, $teamc_score = null, $teamh_score = null)
//     {
//         $sqlReport = Report::whereHas('detail', function($query) use ($gid) {
//             $query->where('table', '=', $gid);
//         })->where('platform_id', $this->platform->id)
//         ->where('status', Report::STATUS_BETTING);

//         // 輸的單也要算流水
//         $reports = $sqlReport->get();

//         foreach($reports as $report)
//         {
//             $report->status = Report::STATUS_COMPLETED;

//             if($cal_bet === true)
//             {
//                 $this->calBetAmount($report->member, $report, $this->platform);
//             }
            

//             // 存入比分
//             if($teamc_score != null && $teamh_score != null)
//             {
//                 $detail = $report->detail;
//                 $content = json_decode($detail->content, true);

//                 $content['hr'] = $teamh_score;
//                 $content['cr'] = $teamc_score;
//                 $content['gameAt'] = $gameAt;
//                 $detail->content = json_encode($content);

//                 $detail->save();
//             }
//         }
        
//         // 因為只回傳贏的，所以其他輸都改成注單完成
//         $sqlReport->update(array('status' => Report::STATUS_COMPLETED, 'settle_at' => $settleAt));
//     }

//     public function reSettle()
//     {
//         // 必須清空注單
//         $requestid = $this->input('lastRequestid');

//         $job = new STGJob('doOrderRESettle', $requestid);

//         dispatch($job);

//         // $logProviders = LogProviderTransaction::where('transaction_id', $requestid)
//         //                 ->where('platform_id', $this->platform->id)
//         //                 ->get();

//         // $logActionRecord = LogProviderActionRecord::where('request_id', $requestid)->first();
//         // // 場次代碼
//         // $gid = $logActionRecord->gid;
//         // // 未異動單號
//         // $not_rollback_report_ids = [];

//         // foreach($logProviders as $logProvider)
//         // {
//         //     // 之前給錢的log
//         //     $logWallet = $logProvider->log;

//         //     // 這次要處理的log
//         //     $rlog            = new LogMemberWallet();
//         //     $rlog->member_id = $logWallet->member_id;
//         //     $rlog->type      = LogMemberWallet::TYPE_ROLLBACK;
//         //     $rlog->type_id   = $logWallet->id;

//         //     DB::beginTransaction();

//         //     try {

//         //         // 如果有發放 正的為發放 負的不管本來就該扣
//         //         // 先收錢 收不到錢的話 就不要繼續了 不然會重複給錢
//         //         // 只要住單的狀態不變成 STATUS_BETTING 就不會重複對獎
//         //         if($logWallet->change_money > 0)
//         //         {
//         //             // 把之前的中獎金額全部拿回來 
//         //             $rlog->change_money = $logWallet->change_money * -1;
                        
//         //             // 扣錢
//         //             // 收不到錢會跳走
//         //             $this->logTransaction($logProvider->report_id, $requestid, $rlog);
//         //         }

//         //         // 修正狀態 從已結算變成投注中 這樣才能重新兌獎
//         //         $update_report_count = Report::where('id', $logWallet->type_id)
//         //             ->where('status', Report::STATUS_COMPLETED)
//         //             ->update([
//         //                 'valid_amount' => 0,
//         //                 'win_amount'   => 0,
//         //                 'status'       => Report::STATUS_BETTING
//         //         ]);

//         //         // 如果沒有任何一筆被更新，就是做過了
//         //         if($update_report_count === 0)
//         //         {
//         //             continue;
//         //         }

//         //         // report 拿回來重算
//         //         $report = Report::findOrError($logWallet->type_id);
//         //         $report->updateTotal();
        
//         //         DB::commit();
//         //     } catch (\Exception $e) {
//         //         DB::rollback();

//         //         $not_rollback_report_ids[] = $logWallet->type_id;

//         //         // 如果發生錢扣不下去,可能是會員沒錢,要記錄起來
//         //         $this->doRecordResetMoneyError($logWallet->member_id, $logWallet->change_money, $logWallet->type_id, $requestid);
    
//         //     } catch (\Throwable $e) {
//         //         DB::rollback();

//         //         $not_rollback_report_ids[] = $logWallet->type_id;

//         //         // 如果發生錢扣不下去,可能是會員沒錢,要記錄起來
//         //         $this->doRecordResetMoneyError($logWallet->member_id, $logWallet->change_money, $logWallet->type_id, $requestid);
    
//         //     }
//         // }

//         // 把這個GID的單 全部變更成下注中 準備重新對獎
//         // $reports = Report::whereHas('detail', function($query) use ($gid) {
//         //     $query->where('table', $gid);
//         // })->where('platform_id', $this->platform->id)
//         // ->where('status', Report::STATUS_COMPLETED)
//         // ->whereNotIn('id', $not_rollback_report_ids)
//         // ->update(['status' => Report::STATUS_BETTING]);


//         return response([
//             'errorcode' => 0,
//             'errormsg'  => '成功'
//         ]);
//     }

//     public function doRecordResetMoneyError($member_id, $money, $report_id, $requestid)
//     {
//         $lmre = new LogMemberResetError();
//         $lmre->platform_id = $this->platform->id;
//         $lmre->request_id = $requestid;
//         $lmre->member_id = $member_id;
//         $lmre->money = $money;
//         $lmre->report_id = $report_id;
//         $lmre->type = LogMemberResetError::TYPE_PENDING;

//         $lmre->save();
//     }

//     public function makeSyncCallBackParameter($row, $settleAt = null)
//     {
//         try {
//             $mem          = $row['mem']; // 會員帳號
//             $orderid      = $row['orderid']; // 注單編號
//             // $gtype        = $row['gtype']; // 球種
//             // $wtype        = $row['wtype']; // 玩法
//             $gid          = $row['gid']; // 賽事ID
//             $gold         = $row['gold']; // 下注金額
//             // $result       = $row['result']; // 下注結果
//             $wingold      = $row['wingold']; // 有效投注
//             $wingold_d    = $row['wingold_d']; //輸贏
//             // $memo         = $row['memo']; // 備註
//             // $tworderdate  = $row['tworderdate']; // 帳務日期
//             $orderdate    = $row['orderdate']; // 賽事時間
//             $adddate      = $row['adddate']; // 下注時間(GMT+8)
//             $type         = $row['type']; // 主客隊
//             $orderIP      = $row['orderIP']; // 下注IP
//             // $betRequestid = $row['betRequestid']; // 下注時的請求ID
//             // $lastdate     = $row['lastdate']; // 最後更新時間
//             $hr           = $row['hr']; // 主隊分數
//             $cr           = $row['cr']; // 客隊分數

//             $wingold_d = $gold + floatval($wingold_d);

//             $format = 'Y-m-d H:i:s';
//             $now   = date($format);

//             $callBackParam           = new SyncCallBackParameter();
//             $callBackParam->mid      = $orderid; // 注單ID
//             $callBackParam->gameCode = 'MG';
//             $callBackParam->username = $mem; // "下注會員帳號
//             $callBackParam->betAt    = (localeDatetime($adddate))->format($format); // 下注時間(GMT+8)
//             $callBackParam->reportAt = $callBackParam->betAt; // 結算時間 改成投注時間，不然會查不到
//             $callBackParam->table    = $gid;
//             $callBackParam->round    = $type;
//             $callBackParam->content  = $hr . '-' . $cr;
//             // $callBackParam->waterAmount = ;
//             $callBackParam->betAmount   = $gold; // 下注時間金額
//             $callBackParam->validAmount = $wingold; // 有效下注
//             $callBackParam->winAmount   = $wingold_d; // 輸贏金額
//             // $callBackParam->prize = ;
//             // $callBackParam->tip = ;
//             $callBackParam->ip     = $orderIP; //下注IP
//             $callBackParam->settleAt = $settleAt;
//             $callBackParam->gameAt = $orderdate;
//             $callBackParam->status = Report::STATUS_COMPLETED;


//             return $callBackParam;

//         } catch (\Throwable $e) {
//             echo 'Caught exception: ' . $e->getMessage() . "\n";
//         } catch (Exception $e) {
//             echo 'Caught exception: ' . $e->getMessage() . "\n";
//         }
        
//         return null;
//     }

//     /**
//      * 同步注單並寫入log
//      *
//      * @param SyncCallBackParameter $rows
//      * @param SyncReportParameter $srp
//      * @return void
//      */
//     public function doSyncReportsSTG($rows, SyncReportParameter $srp, $requestid, $cal_bet = true)
//     {
//         // 先整理資訊，減低資料庫壓力
//         # 注單編號
//         $mids = [];
//         # 遊戲編號(遊戲方)
//         $codes = [];
//         # 玩家 id
//         $playerIds = [];
//         foreach ($rows as $row)
//         {
//             $mids[] = $row->mid;
//             $codes[] = $row->gameCode;
//             $playerIds[] = $row->username;
//         }

//         // 剔除重複的id
//         $codes = array_unique($codes);
//         $playerIds = array_unique($playerIds);

//         // 獲取遊戲
//         $games = $this->getGames($codes, static::GAME_OPTION_MULTI);

//         // 獲取玩家資訊
//         $players = MemberPlatformActive::select('member_id', 'player_id')
//             ->with([
//                 'member' => function (HasOne $hasone) {
//                     return $hasone->select([
//                         'id',
//                         'franchisee_id',
//                         'club_rank_id',
//                         'alv1',
//                         'alv2',
//                         'alv3',
//                         'alv4',
//                         'alv5',
//                         'mlv1',
//                         'mlv2',
//                         'mlv3',
//                     ]);
//                 }
//             ])
//             ->where('platform_id', $this->platform->id)
//             ->whereIn('player_id', $playerIds)
//             ->get()
//             ->keyBy('player_id');

//         // 獲取加盟主設定
//         $fconfigs = FranchiseePlatformConfig::where('platform_id', $this->platform->id)
//             ->whereIn('franchisee_id', $players->pluck('member.franchisee_id')->all() ?: [0])
//             ->get()
//             ->keyBy('franchisee_id');

//         // 獲取俱樂部資訊
//         /** @var ClubRankConfig[] $crcs */
//         $crcs = ClubRankConfig::select('club_rank_id', 'game_id', 'water_percent')
//             ->whereIn('game_id', $games->pluck('id') ?: [0])
//             ->whereIn('club_rank_id', $players->pluck('member.club_rank_id')->all())
//             ->get();

//         $rankConfigs = [];
//         foreach ($crcs as $row)
//         {
//             $rankConfigs["{$row->club_rank_id}-{$row->game_id}"] = $row;
//         }

//         # 取得上層所有上層
//         $aids = $players->map(function ($p) {
//             return $p->member->parentIds();
//         })
//             ->collapse()
//             ->all();
//         $agconfigs = AgentPlatformConfig::select('agent_id', 'percent', 'water_percent', 'bonus_percent')
//             ->whereIn('agent_id', $aids)
//             ->get()
//             ->keyBy('agent_id');

//         $memParents = [];

//         // 取得關聯報表
//         /** @var Report[] $reports */
//         $reports = Report::with([
//             'detail',
//             'member' => function (HasOne $hasone) {
//                 return $hasone->select([
//                     'id',
//                     'franchisee_id',
//                     'club_rank_id',
//                     'alv1',
//                     'alv2',
//                     'alv3',
//                     'alv4',
//                     'alv5',
//                     'mlv1',
//                     'mlv2',
//                     'mlv3',
//                 ]);
//             }
//         ])
//             ->whereIn('mid', $mids)
//             ->where('platform_id', $this->platform->id)
//             ->get()
//             ->keyBy('mid');

//         // 取得遊戲平台的特殊處理設定 不用with的原因是，萬一是新單就會錯囉！
//         // $game_platforms = GamePlatform::select('id', 'special_type')->get()->keyBy('id');

//         $result = new SyncResultFeedback();
//         $result->total = 0;
//         $result->num_completes = 0;
//         $result->num_fails = 0;
//         $fails = [];

//         foreach($rows as $row)
//         {
//             $result->total += 1;

//             $player = $players[$row->username];

//             $game = $games[$row->gameCode] ?? null;
//             if (!$game)
//             {
//                 throw new ErrorException("game not found. code={$row->gameCode}");
//             }

//             $report   = $reports[$row->mid] ?? null;
//             $rankConf = null;
//             $fconf    = null;
//             $parents  = null;

//             if (!$report)
//             {
//                 $member = $player->member;

//                 $key      = "{$member->club_rank_id}-{$game->id}";
//                 $rankConf = $rankConfigs[$key] ?? null;

//                 if ($rankConf === null)
//                 {
//                     throw new ErrorException("club_rank_config not found. key={$key}");
//                 }

//                 $fconf = $fconfigs[$member->franchisee_id] ?? null;

//                 if (!$fconf)
//                 {
//                     throw new ErrorException("franchisee_platform_config not found. fid={$member->franchisee_id}");
//                 }

//                 $parents = $memParents[$member->id] ?? null;
//                 if (!$parents)
//                 {
//                     $parents = [];

//                     for ($lv = 1; $lv <= 5; $lv++)
//                     {
//                         $parents[] = $agconfigs[$player->member->{"alv{$lv}"}];
//                     }

//                     $memParents[$member->id] = $parents;
//                 }
//             }

//             [$report, $detail] = $this->generateReport($player, $game, $report, $row, $rankConf, $fconf, $parents);

//             // 這邊要先做擋單的動作
//             $update_report_count = Report::where('id', $report->id)
//                 ->where('status', Report::STATUS_BETTING)
//                 ->update([
//                     'status' => Report::STATUS_COMPLETED
//             ]);

//             // 如果沒有任何一筆被更新，就是做過了
//             if($update_report_count === 0)
//             {
//                 continue;
//             }

//             try{

//                 DB::beginTransaction();

//                 // 給錢
//                 $log = new LogMemberWallet();
//                 $this->doWalletTransaction($requestid, $report, $detail, $log, LogMemberWallet::TYPE_SETTLE, $cal_bet);

//                 // 計算流水 這邊給錢的時候就會紀錄囉
//                 // $this->calBetAmount($player->member, $report, $game_platforms[$report->platform_id]);

//                 DB::commit();
//                 $result->num_completes += 1;


//             } catch (Exception | \Throwable | \ErrorException $err) {
//                 DB::rollBack();
//                 event(new ThrowException($err));
//                 $result->num_fails += 1;
//                 $fails[] = ['message' => $err->getMessage(), 'row' => $row];
//             }
//         }

//         $result->status = $result->num_fails ? LogSyncReport::STATUS_FAILED : LogSyncReport::STATUS_COMPLETED;
//         $result->fails = $fails;

//         // 寫入log
//         $log                = new LogSyncReport();
//         $log->platform_id   = $this->platform->id;
//         $log->total         = $result->total;
//         $log->num_completes = $result->num_completes;
//         $log->num_fails     = $result->num_fails;
//         $log->stime         = $srp->startAt;
//         $log->etime         = $srp->endAt;
//         $log->fails         = $result->fails;
//         $log->message       = [];
//         $log->status        = $result->status;
//         $log->saveOrError();

//         return $result;
//     }
// }