<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\GamePlatform;
use App\Models\Game;
use GameProvider\Operator\BaseApi;

use GameProvider\Operator\Multi\Config\RWDConfigConstract;

use GameProvider\Operator\Multi\BaseMultiWalletInterface;

use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\TransferParameter;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;

use GameProvider\Exceptions\LoginException;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\CreateMemberException;
use GameProvider\Exceptions\LaunchGameException;
use GameProvider\Exceptions\SyncException;
use GameProvider\Exceptions\TransferException;

use GameProvider\Operator\Feedback\MemberFeedback;
use GameProvider\Operator\Feedback\TransferFeedback;
use GameProvider\Operator\Feedback\BalanceFeedback;
use GameProvider\Operator\Feedback\LaunchGameFeedback;

use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Support\Str;

class RWD extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $curlHeader = ["Content-Type:application/json"];

    function __construct(array $config)
    {
        $this->config = new RWDConfigConstract();
        $this->config->apiUrl = $config['apiUrl'];
        $this->config->vendorAcc = $config['vendorAcc'];
        $this->config->vendorPwd = $config['vendorPwd'];
        $this->config->key = $config['key'];
        $this->config->lang = $config['lang'];
        $this->config->currency = $config['currency'];

        if (substr($this->config->apiUrl, -1) != '/') {     // 怕後台設定錯，保險加
            $this->config->apiUrl .= '/';
        }
    }

    public function getGameList()
    {
    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $params = [
            'spId'      => $this->config->vendorAcc,
            'account'   => $member->playerId,
            'requestTime'=> date('YmdHis'),
        ];

        $memberFeedback = new MemberFeedback();

        $url = $this->config->apiUrl.'wallet/createPlayer';

        $result = $this->doSendProcess($params, $url);

        if ($result->retCode === '0') {
            $testBalance = $this->doSendProcess($params, $this->config->apiUrl.'wallet/getPlayerInfo');
            if ($testBalance && $testBalance->retCode == 'ACCOUNT_NOT_EXIST') { // 迷之原因沒有創成功 再創一次
                $result = $this->doSendProcess($params, $url);
            }
            return $memberFeedback;
        }

        throw new CreateMemberException(get_class($this), 'create member error! error code : ' . $result->retCode. ' playerId: '.$member->playerId);
    }

    /**
     * 取得會員餘額
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        $params = [
            'spId'      => $this->config->vendorAcc,
            'account'   => $member->playerId,
            'requestTime'=> date('YmdHis'),
        ];

        $url = $this->config->apiUrl.'wallet/getPlayerInfo';

        $result = $this->doSendProcess($params, $url);

        $balanceFeedback = new BalanceFeedback();

        if ($result->retCode == '0') {
            $balanceFeedback->balance = ($result->data->balance / 100);
            return $balanceFeedback;
        }

        throw new BalanceException(get_class($this), 'get balance error! error code : ' .  $result->retCode. ' playerId: '.$member->playerId);
    }

    /**
     * 會員登入（取得遊戲路徑）
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */

    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        // 判斷是否為彩票遊戲
        $platform = GamePlatform::select('id')
        ->where('key', 'RWD')
        ->first();

        if (!$platform) {
            throw new LoginException(get_class($this), 'auth error! platform id not found.');
        }

        $platformId = $platform['id'];

        $games = Game::select('type')
        ->where('code', $launchGameParams->gameId)
        ->where('platform_id', $platformId)
        ->first();

        $gameId = $launchGameParams->gameId;

        if ($games && $games['type'] == 'lottery') {
            $gameId = 'bb1'; // 彩票大廳
        }

        $params = [
            'spId'      => $this->config->vendorAcc,
            'productId' => $gameId,
            'returnUrl' => "www.google.com",
            'account'   => $launchGameParams->member->playerId,
            'requestTime'=> date('YmdHis'),
        ];

        $device = "pc";
        if ($launchGameParams->device == "mobile") {
            $device = "mobile";
        }

        $url = $this->config->apiUrl.'launch/'.$device;

        $result = $this->doSendProcess($params, $url);

        $launchGameFeedback = new LaunchGameFeedback();

        if ($result->retCode == '0') {
            $launchGameFeedback->gameUrl = $result->data->gameUrl;
            return $launchGameFeedback;
        }

        throw new LaunchGameException(get_class($this), 'launch game error! error code : ' . $result->retCode);
    }


    /**
     * 存款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $payno = str_replace('-', '', Str::uuid());

        $params = [
            'spId'      => $this->config->vendorAcc,
            'tranId'    => $payno,
            'account'   => $transfer->member->playerId,
            'requestTime'=> date('YmdHis'),
            'type'      => 0,
            'amount'    => $transfer->amount * 100,
        ];

        $url = $this->config->apiUrl.'wallet/tran';

        $result = $this->doSendProcess($params, $url);

        $transferFeedback = new TransferFeedback();

        if($result->retCode == '0')
        {
            $transferFeedback->balance = $result->data->afterBalance / 100;
            $transferFeedback->remote_payno = $result->data->orderId;
            return $transferFeedback;
        }

        throw new TransferException(get_class($this), 'deposit error! error code : ' . $result->retCode);
    }

     /**
     * 提款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $payno = str_replace('-', '', Str::uuid());

        $params = [
            'spId'      => $this->config->vendorAcc,
            'tranId'    => $payno,
            'account'   => $transfer->member->playerId,
            'requestTime'=> date('YmdHis'),
            'type'      => 1,
            'amount'    => $transfer->amount * 100,
        ];

        $url = $this->config->apiUrl.'wallet/tran';

        $result = $this->doSendProcess($params, $url);

        $transferFeedback = new TransferFeedback();

        if($result->retCode == '0')
        {
            $transferFeedback->balance = $result->data->afterBalance / 100;
            $transferFeedback->remote_payno = $result->data->orderId;
            return $transferFeedback;
        }

        throw new TransferException(get_class($this), 'withdraw error! error code : ' . $result->retCode);
    }

     /**
     * 同步注單(取回時間區段的所有注單)
     *
     * 限制1分鐘存取2次
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $format = 'YmdHi';
        $startAt = Carbon::parse($srp->startAt)->format($format);
        $reportRange = Carbon::parse($srp->endAt)->diffInMinutes(Carbon::parse($srp->startAt));

        $params = [
            'spId'      => $this->config->vendorAcc,
            'time'      => $startAt,
            'timeRange' => $reportRange,
            'requestTime'=> date('YmdHis'),
        ];

        return $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
        $url = $this->config->apiUrl.'datasouce/getBetRecordByBetAndPayoutTime';

        $result = $this->doSendProcess($params, $url);

        if($result->retCode !== '0')
        {
            throw new SyncException(get_class($this), 'syncReport error! error code : '.$result->retCode);
        }

        if(!isset($result->data) || !isset($result->data->record))
        {
            throw new SyncException(get_class($this), 'syncReport error! error format : '.json_encode($result));
        }

        $rows = $result->data->record;

        $data = [];

        foreach($rows as $game)
        {
            foreach ($game->betDetail as $bet) {
                $data[] = $this->makeSyncCallBackParameter($game, $bet);
            }
        }

        return $data;
    }

    private function makeSyncCallBackParameter($row, $bet)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid = $row->betId.'_'.$bet->betDetailId;    // 單號
        $callBackParam->username = $row->account;   // 帳號
        $callBackParam->round = $row->gameNumber;   // 局號
        $callBackParam->gameCode = $row->gameName;  // 遊戲code
        $callBackParam->betAmount = $bet->betAmt / 100;   // 下注金額
        $callBackParam->content = $bet->content;    // 下注內容
        $callBackParam->winAmount = ($bet->earn + $bet->betAmt) / 100;  // 贏
        $callBackParam->betAt    = date('Y-m-d H:i:s', strtotime($row->betTime));   // 下注時間
        $callBackParam->reportAt = $callBackParam->betAt;
        $callBackParam->settleAt = date('Y-m-d H:i:s', strtotime($bet->payoutTime)); //結算時間

        switch ($bet->status) { // 注單狀態
            case '0':
                $callBackParam->status = Report::STATUS_BETTING;
                break;
            case '1':
                $callBackParam->status = Report::STATUS_COMPLETED;
                break;
            case 'X':
                $callBackParam->status = Report::STATUS_CANCEL;
                break;
            default:
                $callBackParam->status = Report::STATUS_ERROR;
                break;
        }

        $valid = $bet->betAmt;
        if ($bet->betAmt > abs($bet->earn)) {
            $valid = abs($bet->earn);
        }
        $valid = $valid / 100;
        $callBackParam->validAmount = $valid;  // 有效下注

        //$row->result; // 遊戲內容結果

        return $callBackParam;
    }

    private function doSendProcess($params, $apiUrl)
    {
        ksort($params);

        $sign = md5(http_build_query($params).'&key='.$this->config->key);

        $params["sign"] = $sign;

        $fullParams = json_encode($params);

        $result = $this->post($apiUrl, $fullParams);

        return $result;
    }

}
