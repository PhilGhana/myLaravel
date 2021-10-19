<?php

namespace GameProvider\Services;

use App\Exceptions\ErrorException;
use App\Models\GamePlatform;
use App\Models\LogMemberWallet;
use App\Models\LogProviderTransaction;
use App\Models\Member;
use App\Models\MemberPlatformActive;
use App\Models\MemberWallet;
use App\Models\Report;
use App\Services\Provider\AccessService;
use DB;
use Exception;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\ForbiddenException;
use GameProvider\Exceptions\FundsExceedException;
use GameProvider\Exceptions\SaveFailedException;
use GameProvider\Exceptions\StuckMoneyException;
use GameProvider\Exceptions\TransferException;
use GameProvider\Operator\Feedback\LaunchGameFeedback;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;
use GameProvider\Operator\Single\BaseSingleWalletInterface;

class Sg2Service extends BaseWalletService
{
    /**
     * 轉接環.
     *
     * @var BaseSingleWalletInterface
     */
    protected $api;

    public function __construct()
    {
        $this->platform = GamePlatform::where('key', 'SG')->first();
        $this->key      = 'SG';
        $this->api      = $this->platform->getPlatformModule();
    }

    /**
     * 執行動作（路由）.
     *
     * @param string $action
     * @param object $parameter
     * @return void
     */
    public function action($action, $parameter)
    {
        switch ($action) {
            case 'launchGame':
                return $this->launchGame($parameter);
                break;
            case 'syncReport':
                return $this->syncReport($parameter);
                break;
            case 'authorize':
                return $this->authorize();
                break;
            case 'getBalance':
                return $this->getBalance();
                break;
            case 'transfer':
                return $this->transfer();
                break;
            default:
                throw new Exception('SG do not have '.$action.' action !');
        }
    }

    /**
     * 交易接口.
     *
     * @return void
     */
    public function transfer()
    {
        $parameter               = new SyncCallBackParameter();
        $parameter->mid          = $this->input('ticketId');
        $parameter->uid          = $this->input('transferId');
        $parameter->username     = $this->input('acctId');
        $parameter->betAmount    = $this->input('amount');
        $parameter->validAmount  = $this->input('amount');
        $parameter->gameCode     = $this->input('gameCode');
        $parameter->merchantCode = $this->input('merchantCode');
        $parameter->serialNo     = $this->input('serialNo');
        // 以下沒用到是因為沒有回傳
        // $parameter->winAmount   = null;
        // $parameter->betAt       = null;
        // $parameter->reportAt    = null;
        // $parameter->ip          = null;
        // $parameter->round       = null;
        // $parameter->content     = null;

        $transParameter                    = new TransferParameter();
        $transParameter->merchantCode      = $this->input('merchantCode');
        $transParameter->serialNo          = $this->input('serialNo');
        $transParameter->referenceId       = $this->input('referenceId');
        $transParameter->amount            = $this->input('amount');
        $transParameter->syncCallBackParam = $parameter;

        // 檢查這張單有沒有處理過
        $checkResult = $this->checkProviderTransaction($parameter->uid);

        if ($checkResult === true) {
            return json_encode($this->generateTransfer($logProvider->id, $logProvider->log->after_money, $transParameter));
        }

        // 下注
        if ($type == 1) {
            $parameter->status = Report::STATUS_BETTING;

            return $this->payOut($transParameter);
        }

        // 取消下注
        if ($type == 2) {
            $parameter->status = Report::STATUS_CANCEL;

            $this->rollback($transParameter);
        }

        // 派彩（等於完成注單）
        if ($type == 4) {
            $parameter->status = Report::STATUS_COMPLETED;

            return $this->payIn($transParameter);
        }

        // 紅包（直接完成注單）
        if ($type == 7) {
            $parameter->status = Report::STATUS_COMPLETED;
            $this->payPrize($transParameter);
        }
    }

    /**
     * 派彩或對方出點數給平台.
     *
     * @param TransferParameter $parameter
     * @return void
     */
    private function payIn(TransferParameter $parameter)
    {
        // 這邊不動注單，就算有資訊也依樣，客戶不會在乎點數變多了，直接把這裡交給同步去處理就好
        // 因為各種情況太多了，獨立處理有太多例外，所以只處理點數
        $active = $this->getPlatformActive($parameter->username);

        // 特例，當有referenceId時，要找是不是存在，不然應該要報錯給遊戲商
        // 因為不存在轉入，相對就不應該有派彩
        $logProvider = LogProviderTransaction::where('transaction_id', $parameter->referenceId)
            ->where('platform_id', $this->platform->id)
            ->first();

        if (! $logProvider) {
            return json_encode($this->generateResponse($parameter->serialNo, '109', 'error'));
        }

        $amount = $parameter->amount;
        // 避免金額正負錯誤
        if ($amount > 0) {
            $amount = -floatval($amount);
        }

        $log               = new LogMemberWallet();
        $log->member_id    = $active->member_id;
        $log->type         = LogMemberWallet::TYPE_SETTLE;
        $log->type_id      = $logProvider->report_id;
        $log->change_money = $amount;

        // 如果有注單就順便綁起來，但是不動內容，一切交給同步比對
        $report = Report::where('id', $logProvider->report_id)
            ->where('platform_id', $this->platform->id)
            ->first();

        $rid = 0;

        if ($report) {
            $rid = $report->id;
        }

        return DB::transaction(function () use ($parameter, $log, $rid) {
            $logProvider = $this->logTransaction($rid, $parameter->syncCallBackParam->uid, $log);

            return $this->generateTransfer($logProvider->id, $logProvider->log->after_money, $parameter);
        });
    }

    /**
     * 優惠（紅包）.
     *
     * @param TransferParameter $parameter
     * @return void
     */
    private function payPrize(TransferParameter $parameter)
    {
        $active   = $this->getPlatformActive($parameter->member->playerId);

        $log = new LogMemberWallet();

        // 紅包類，直接加開注單
        // 先給一個虛的遊戲名稱
        $gameCode = $parameter->syncCallBackParam->gameCode;
        $game     = $this->getGames($gameCode, static::GAME_OPTION_FIRST);

        [$report, $detail] = $this->generateReport($active, $game, null, $parameter->syncCallBackParam);

        return DB::transaction(function () use ($parameter, $report, $log) {

            // 更新各層額度比例
            $changeValue = $report->updateTotal();

            if (! $report->save()) {
                throw new SaveFailedException('report');
            }

            $detail->id = $report->id;
            if (! $detail->save()) {
                throw new SaveFailedException('report-detail');
            }

            // 建立彩金資料
            $prize = $this->generatePrize($report, $parameter);
            $prize->save();

            if ($changeValue) {
                $log->member_id    = $report->member_id;
                $log->type         = LogMemberWallet::TYPE_PRIZE;
                $log->type_id      = $report->id;
                $log->change_money = $changeValue;

                $logProvider = $this->logTransaction($report->id, $parameter->syncCallBackParam->uid, $log);

                return $this->generateTransfer($logProvider->id, $logProvider->log->after_money, $parameter);
            }
        });
    }

    /**
     * 把點數轉出去，一般用在投注時
     *
     * @param TransferParameter $parameter
     * @return void
     */
    private function payOut(TransferParameter $parameter)
    {
        $platform = $this->platform;
        $active   = $this->getPlatformActive($parameter->syncCallBackParam->username);

        // 查錢夠不夠
        $wallet = $this->getWallet($active->member_id);
        if ($wallet->money < $parameter->amount) {
            throw new FundsExceedException(__('provider.found-exceed'));
        }

        // 檢查是否有使用者
        $member = Member::findOrError($active->member_id);
        $this->checkMemberBetPermission($member);

        // 沒資料不管怎麼樣都要生出來一張單，避免同步前對不上
        // 拿注單
        $report = Report::where('platform_id', $platform->id)
            ->where('mid', $parameter->syncCallBackParam->mid)->first();

        $detail = $report ? $report->detail : null;

        $gameCode = $parameter->syncCallBackParam->gameCode;

        $game = $this->getGames($gameCode, static::GAME_OPTION_FIRST);

        [$report, $detail] = $this->generateReport($active, $game, $report, $parameter->syncCallBackParam);

        $log = new LogMemberWallet();

        return DB::transaction(function () use ($parameter, $report, $detail, $log) {
            $amount = $report->updateTotal($detail);

            if (! $report->save()) {
                throw new SaveFailedException('report');
            }

            $detail->id = $report->id;
            if (! $detail->save()) {
                throw new SaveFailedException('report-detail');
            }

            // 等 report 建完後才扣款
            if (LogMemberWallet::isVerify($amount)) {
                $log->member_id    = $report->member_id;
                $log->type         = LogMemberWallet::TYPE_BET;
                $log->type_id      = $report->id;
                $log->change_money = $amount;
                // 更新交易記錄
                $logProvider = $this->logTransaction($report->id, $parameter->syncCallBackParam->uid, $log);

                return $this->generateTransfer($logProvider->id, $logProvider->log->after_money, $parameter);
            } else {
                throw new ErrorException('error amount');
            }
        });
    }

    /**
     * 回滾
     * 把點數轉進來，用在取消注單時、移除派彩、取消彩金、取消紅包.
     *
     * @param TransferParameter $parameter
     * @return void
     */
    private function rollback(TransferParameter $parameter)
    {
        $active = $this->getPlatformActive($parameter->member->playerId);

        return DB::transaction(function () use ($active) {

            // 把當時的金額取回來看看
            $logTransaction = LogProviderTransaction::where('platform_id', $this->platform->id)
                ->with('log')
                ->with('report')
                ->where('transaction_id', $parameter->referenceId)
                ->first();

            // 沒有轉帳記錄不應該取消注單
            if (! $logTransaction) {
                return $this->generateResponse($parameter->serialNo, '2', 'Invalid Request');
            }

            $report    = $logTransaction->report;
            $logWallet = $logTransaction->log;

            $rlog            = new LogMemberWallet();
            $rlog->member_id = $active->member_id;
            $rlog->type      = LogMemberWallet::TYPE_ROLLBACK;
            $rlog->type_id   = $logWallet->id;

            // 這個狀況rollback就是退錢
            $report->bet_amount += $logWallet->change_money;

            // 異動金額
            $rlog->change_money = $report->updateTotal();

            $logProvider = $this->logTransaction($logTransaction->report_id, $parameter->syncCallBackParam->uid, $rlog);

            return $this->generateTransfer($logProvider->id, $logProvider->log->after_money, $parameter);
        });
    }

    /**
     * 對方來查餘額.
     *
     * @return string
     */
    public function getBalance()
    {
        $playerId     = $this->input('acctId');
        $merchantCode = $this->input('merchantCode');
        $serialNo     = $this->input('serialNo');
        $code         = 0;
        $msg          = 'success';

        // 查是不是我們
        [$code, $msg] = $this->checkMerchantCode($merchantCode, $code, $msg);

        // 查有沒有這個使用者
        [$active, $code, $msg] = $this->getMemberPlatform($playerId, $code, $msg);

        $response = $this->generateResponse($serialNo, $code, $msg);

        if ($code === 0) {
            $response = $this->generateInfo($active, $playerId, $response);
        }

        return json_encode($response);
    }

    /**
     * 當請求進入遊戲時，對方會來驗證是不是有這個請求
     *
     * @return string
     */
    public function authorize()
    {
        $playerId     = $this->input('acctId');
        $token        = $this->input('token');
        $merchantCode = $this->input('merchantCode');
        $serialNo     = $this->input('serialNo');
        $code         = 0;
        $msg          = 'success';

        // 查token
        [$code, $msg] = $this->checkToken($token);

        // 查是不是我們
        [$code, $msg] = $this->checkMerchantCode($merchantCode, $code, $msg);

        // 查有沒有這個使用者
        [$active, $code, $msg] = $this->getMemberPlatform($playerId, $code, $msg);

        $response = $this->generateResponse($serialNo, $code, $msg);

        if ($code === 0) {
            $response = $this->generateInfo($active, $playerId, $response);
        }

        return json_encode($response);
    }

    /**
     * 以下為我們去問對方.
     */

    /**
     * 進入遊戲.
     *
     * @param LaunchGameParameter $LGP
     * @return LaunchGameFeedback
     */
    public function launchGame(LaunchGameParameter $LGP)
    {
        $member = Member::findOrError($LGP->member->member_id);

        $this->checkLaunchGame($LGP->gameId, $member);

        $LGP->token = $this->setAuthorizeToken();

        return $this->api->launchGame($LGP);
    }

    /**
     * 同步作業.
     *
     * @param SyncReportParameter $srp
     * @return void
     */
    public function syncReport(SyncReportParameter $srp)
    {
        return $this->api->syncReport($srp, function ($rows) use ($srp) {
            return $this->doSyncReports($rows, $srp);
        });
    }

    /**
     * 產生交易回傳.
     *
     * @param int $id 我方的單號
     * @param float $balance 餘額
     * @param TransferParameter $transParameter
     * @return void
     */
    private function generateTransfer($id, $balance, TransferParameter $transParameter)
    {
        $response = $this->generateResponse($transParameter->serialNo, '0', 'success');
        $response = array_merge($response, [
            'transferId'   => $transParameter->syncCallBackParam->uid,
            'merchantTxId' => $id,
            'acctId'       => $transParameter->syncCallBackParam->username,
            'balance'      => $balance,
        ]);

        return $response;
    }

    /**
     * 對方來查詢時，產生使用者資訊.
     *
     * @param MemberPlatformActive $active
     * @param string $playerId
     * @param array $response
     * @return array
     */
    private function generateInfo($active, $playerId, $response)
    {
        // 查餘額
        $wallet = MemberWallet::findOrError($active->member_id);

        $acctInfo = [
            'acctId'   => $playerId,
            'balance'  => $wallet->money,
            'currency' => $this->platform->setting['currency'],
        ];

        $response['acctInfo'] = $acctInfo;

        return $response;
    }

    /**
     * 對方來查詢時，產生主要的response.
     *
     * @param string $serialNo
     * @param int $code
     * @param string $msg
     * @return array
     */
    private function generateResponse($serialNo, $code, $msg)
    {
        $response = [
            'merchantCode' => $this->platform->setting['merchantCode'],
            'msg'          => $msg,
            'code'         => $code,
            'serialNo'     => $serialNo,
        ];

        return $response;
    }

    /**
     * 對方來查詢時，檢查是不是有這個使用者.
     *
     * @param string $playerId
     * @param int $code
     * @param string $msg
     * @return [$active, $code, $msg]
     */
    private function getMemberPlatform($playerId, $code, $msg)
    {
        $active = MemberPlatformActive::where('platform_id', $this->platform->id)
                ->where('player_id', $playerId)
                ->first();

        if (! $active) {
            $newCode = 50100;
            $newMsg  = 'Acct Not Found ';

            return [$newCode, $newMsg];
        }

        return [$active, $code, $msg];
    }

    private function checkToken($token)
    {
        // 查token是否存在
        if (! $this->checkAuthorizeTokenValid($token)) {
            $newCode = 50104;
            $newMsg  = 'inValid token';

            return [$newCode, $newMsg];
        }

        // 查token是否過期
        if ($this->checkAuthorizeTokenExpire($token) === false) {
            $newCode = 50104;
            $newMsg  = 'token expired';

            return [$newCode, $newMsg];
        }

        return [$code, $msg];
    }

    /**
     * 對方來查詢時，檢查是不是丟錯別人的給我們.
     *
     * @param string $merchantCode
     * @param int $code
     * @param string $msg
     * @return [$code, $msg]
     */
    private function checkMerchantCode($merchantCode, $code, $msg)
    {
        if ($merchantCode != $this->platform->setting['merchantCode']) {
            $newCode = 10113;
            $newMsg  = 'Merchant Not Found';

            return [$newCode, $newMsg];
        }

        return [$code, $msg];
    }

    /*
     * 取得玩家 MemberPlatformActive 資料
     *
     * @param string $playerId
     * @return MemberPlatformActive
     */
    // private function getPlatformActive(string $playerId)
    // {
    //     $active = MemberPlatformActive::where('platform_id', $this->platform->id)
    //             ->where('player_id', $playerId)
    //             ->first();

    //     if (!$active)
    //     {
    //         throw new ErrorException('player not found : ' . $playerId . "," . $this->platform->id);
    //     }

    //     return $active;
    // }
}
