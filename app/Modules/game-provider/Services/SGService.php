<?php

namespace GameProvider\Services;

use App\Services\Provider\AccessService;

use GameProvider\Operator\Single\BaseSingleWalletInterface;

use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\TransferParameter;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;

use GameProvider\Operator\Feedback\LaunchGameFeedback;

use App\Models\GamePlatform;
use App\Models\MemberPlatformActive;
use App\Models\MemberWallet;
use App\Models\Report;
use App\Models\LogMemberWallet;

use App\Exceptions\ErrorException;
use Exception;
use GameProvider\Exceptions\TransferException;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\FundsExceedException;
use GameProvider\Exceptions\StuckMoneyException;

class SgService extends BaseWalletService
{
    /**
     * 轉接環
     *
     * @var BaseSingleWalletInterface
     */
    protected $api;

    function __construct()
    {
        $this->platform = GamePlatform::where('key', 'SG')->first();
        $this->api      = $this->platform->getPlatformModule();
    }

    /**
     * 執行動作
     *
     * @param string $action
     * @param object $parameter
     * @return void
     */
    public function action($action, $parameter)
    {
        switch($action)
        {
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
            default:
                throw new Exception('SG do not have ' . $action . ' action !');
        }
    }

    public function transfer()
    {
        $playerId     = $this->input('acctId');
        $merchantCode = $this->input('merchantCode');
        $serialNo     = $this->input('serialNo');
        $transferId   = $this->input('transferId');
        // $currency     = $this->input('currency');
        $amount       = $this->input('amount');
        $type         = $this->input('type');
        $gameCode     = $this->input('gameCode');
        // $ticketId     = $this->input('ticketId');
        // $referenceId  = $this->input('referenceId');
        // $specialGame  = $this->input('specialGame');
        // $refTicketIds = $this->input('refTicketIds');
        $code         = 0;
        $msg          = 'success';

        // 因為會給注單資料，所以先建立物件備用
        $parameter              = new SyncCallBackParameter();
        $parameter->mid         = $transferId;
        $parameter->username    = $playerId;
        $parameter->betAmount   = $amount;
        $parameter->validAmount = $amount;
        $parameter->gameCode    = $gameCode;
        // 以下沒用到是因為沒有回傳
        // $parameter->winAmount   = null;
        // $parameter->betAt       = null;
        // $parameter->reportAt    = null;
        // $parameter->ip          = null;
        // $parameter->round       = null;
        // $parameter->content     = null;

        // 下注
        if($type == 1)
        {
            $parameter->status = Report::STATUS_BETTING;
        }

        // 取消下注
        if($type == 2)
        {
            $parameter->status = Report::STATUS_CANCEL;
        }

        // 派彩（等於完成注單）
        if($type == 4)
        {
            $parameter->status = Report::STATUS_COMPLETED;
        }
    }

    /**
     * 對方來查餘額
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

        if($code === 0)
        {
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
        $this->checkToken($token, $code, $msg);

        // 查是不是我們
        [$code, $msg] = $this->checkMerchantCode($merchantCode, $code, $msg);

        // 查有沒有這個使用者
        [$active, $code, $msg] = $this->getMemberPlatform($playerId, $code, $msg);

        $response = $this->generateResponse($serialNo, $code, $msg);

        if($code === 0)
        {
            $response = $this->generateInfo($active, $playerId, $response);
        }

        return json_encode($response);
    }

    /**
     * 進入遊戲
     *
     * @param LaunchGameParameter $LGP
     * @return LaunchGameFeedback
     */
    public function launchGame(LaunchGameParameter $LGP)
    {
        $this->checkLaunchGame($LGP->gameId);

        $active = MemberPlatformActive::where('player_id', $LGP->member->playerId)
                ->where('platform_id', $this->platform->id)
                ->first();

        if (!$active)
        {
            throw new ErrorException("player not found > {$playerId}");
        }

        $access = new AccessService($active);

        $LGP->token = $access->generateAccessToken();

        return $this->api->launchGame($LGP);
    }

    /**
     * 同步作業
     *
     * @param SyncReportParameter $srp
     * @return void
     */
    public function syncReport(SyncReportParameter $srp)
    {
        return $this->api->syncReport($srp, function($rows) use ($srp){
            return $this->doSyncReports($rows, $srp);
        });
    }

    /**
     * 對方來查詢時，產生使用者資訊
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
     * 對方來查詢時，產生主要的response
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
            'serialNo'     => $serialNo
        ];

        return $response;
    }

    /**
     * 對方來查詢時，檢查是不是有這個使用者
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

        if (!$active)
        {
            $newCode = 50100;
            $newMsg  = 'Acct Not Found ';

            return [$newCode, $newMsg];
        }

        return [$active, $code, $msg];
    }

    private function checkToken($token, $code, $msg)
    {
        // 查token是否存在
        if (!AccessService::isValidAccessToken($token))
        {
            $newCode = 50104;
            $newMsg  = 'inValid token';

            return [$newCode, $newMsg];
        }

        // 查token是否過期
        $active = AccessService::veirfyAccessToken($token);
        if (!$active)
        {
            $newCode = 50104;
            $newMsg  = 'token expired';

            return [$newCode, $newMsg];
        }

        return [$code, $msg];
    }

    /**
     * 對方來查詢時，檢查是不是丟錯別人的給我們
     *
     * @param string $merchantCode
     * @param int $code
     * @param string $msg
     * @return [$code, $msg]
     */
    private function checkMerchantCode($merchantCode, $code, $msg)
    {
        if($merchantCode != $this->platform->setting['merchantCode'])
        {
            $newCode = 10113;
            $newMsg  = 'Merchant Not Found';

            return [$newCode, $newMsg];
        }

        return [$code, $msg];
    }
}
