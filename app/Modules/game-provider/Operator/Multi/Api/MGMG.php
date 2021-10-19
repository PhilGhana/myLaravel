<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Game;
use GameProvider\Operator\BaseApi;

use GameProvider\Operator\Multi\Config\MGMGConfigConstract;

use GameProvider\Operator\Multi\BaseMultiWalletInterface;

use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\TransferParameter;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;

use GameProvider\Exceptions\AesException;
use GameProvider\Exceptions\LoginException;
use GameProvider\Exceptions\GameListException;
use GameProvider\Exceptions\JSONException;
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

class MGMG extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $curlHeader = ['Content-Type:application/json;charset=utf-8'];

    function __construct(array $config)
    {
        $this->config = new MGMGConfigConstract();

        $this->config->apiUrl = $config['apiUrl'];
        $this->config->agent = $config['agent'];
        $this->config->key = $config['key'];
        // $this->config->language = $config['language'];
    }


    public function getGameList()
    {

        // $url = $this->config->apiUrl.'getGameList';

        // $params = [
        //     'agent'     => $this->config->agent,
        //     'timestamp' => time()
        // ];

        // $result = $this->doSendProcess($params, $url);

        // if($result->code == '0')
        // {

        // }
        // 寫入資料庫
        // foreach($data as $row)
        // {
        //     // $game = new Game();
        //     $game = Game::where('code', $row->id)->where('platform_id', 13)->first();
        //     if($game)
        //     {
        //         $game->name_en = $row->name;
        //         $game->name_zh_cn = $row->name;
        //         $game->name_zh_tw = $row->name;
        //         $game->name_jp = $row->name;
        //         // $game->image = $row->src->image_m;
        //         $game->code = $row->code;
        //         $game->code_mobile = $row->code;
        //         $game->launch_method = 'GET';
        //         $game->enabled = 1;
        //         $game->platform_id = 13;
        //         $game->type = 'live';
        //         $game->save();
        //     }

        // }
    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
		$url = $this->config->apiUrl.'login';

        $platform = 'PC';

        $ip = '127.0.0.1';
        if ($member->ip) {
            $ip = $member->ip;
        }

        $gameId = 201;

        $params = [
            'account'   => $member->playerId,
            'gameId'    => $gameId,
            'ip'        => $ip,
            'agent'     => $this->config->agent,
            'platform'  => $platform,
            // 'appUrl'    => 'www.google.com',
            // 'exitUrl'   => 'www.google.com',
            'theme'     => 'S001',
            // 'p1'        => '',
            // 'p2'        => '',
            // 'token'     => '',
            'timestamp' => time()
        ];

        $result = $this->doSendProcess($params, $url);

        if ($result->code == 0) {
        	$memberFeedback = new MemberFeedback();
        	return $memberFeedback;
        }

        throw new CreateMemberException(get_class($this), 'create member error! error code : ' . $result->code . '. ' . $result->msg);
    }

    /**
     * 取得會員餘額
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        $url = $this->config->apiUrl.'queryUserScore';

        $params = [
            'account'   => $member->playerId,
            'agent'     => $this->config->agent,
            'timestamp' => time()
        ];

        $balanceFeedback = new BalanceFeedback();

        $result = $this->doSendProcess($params, $url);

        if($result->code == '0')
        {
            $balanceFeedback->balance = $result->data->money;
            return $balanceFeedback;
        }

        throw new BalanceException(get_class($this), 'get balance error! error code : ' . $result->code . '. ' . $result->msg);
    }

    /**
     * 會員登入（取得遊戲路徑）
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */

    public function launchGame(LaunchGameParameter $launchGameParams)
    {

        $url = $this->config->apiUrl.'login';

        $platform = 'PC';
        if ($launchGameParams->device == 'mobile') {
            $platform = 'WAP';
        }

        $ip = '127.0.0.1';
        if ($launchGameParams->member->ip) {
            $ip = $launchGameParams->member->ip;
        }

        $params = [
            'account'   => $launchGameParams->member->playerId,
            'gameId'    => $launchGameParams->gameId,
            'ip'        => $ip,
            'agent'     => $this->config->agent,
            'platform'  => $platform,
            'theme'     => 'S001',
            'timestamp' => time(),
            // 'appUrl'    => 'www.google.com',
            // 'exitUrl'   => 'www.google.com',
            // 'p1'        => '',
            // 'p2'        => '',
            // 'token'     => ''
        ];

        $launchGameFeedback = new LaunchGameFeedback();

        $result = $this->doSendProcess($params, $url);

        if ($result->code == '0') {
            $launchGameFeedback->gameUrl = $result->data->url;
            return $launchGameFeedback;
        }
        throw new LaunchGameException(get_class($this), 'launchGame error! error code : ' . $result->code . '. ' . $result->msg);
    }


    /**
     * 存款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $url = $this->config->apiUrl.'doTransferDepositTask';
        $payno = str_replace('-', '', Str::uuid());

        $params = [
            'account'   => $transfer->member->playerId,
            'agent'     => $this->config->agent,
            'money'     => $transfer->amount,
            'orderId'   => $payno,
            'timestamp' => time()
        ];
        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($params, $url);

        if($result->code == 0)
        {
            $transferFeedback->remote_payno = $result->data->orderId;
            return $transferFeedback;
        }

        throw new TransferException(get_class($this), 'deposit error! error code : '. $result->code . '. ' . $result->msg);
    }

     /**
     * 提款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $url = $this->config->apiUrl.'doTransferWithdrawTask';
        $payno = str_replace('-', '', Str::uuid());

        $params = [
            'account'   => $transfer->member->playerId,
            'agent'     => $this->config->agent,
            'money'     => $transfer->amount,
            'orderId'   => $payno,
            'timestamp' => time()
        ];
        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($params, $url);

        if($result->code == 0)
        {
            $transferFeedback->remote_payno = $result->data->orderId;
            return $transferFeedback;
        }

        throw new TransferException(get_class($this), 'deposit error! error code : '. $result->code . '. ' . $result->msg);
    }

     /**
     * 同步注單(取回時間區段的所有注單)
     *
     * 限制10秒存取一次
     * 查詢區間限制為10分鐘
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
    	$PageSize = 100;
        $format = 'Y-m-d H:i:s';
        $startAt = Carbon::parse($srp->startAt)->format($format);
        $endAt = Carbon::parse($srp->endAt)->format($format);

        $params = [
            'agent'     => $this->config->agent,
            'startTime' => $startAt,
            'endTime'   => $endAt,
            'size'   	=> $PageSize,
            'page'   	=> 0,
            'timestamp' => time()
        ];

        return $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
    	$url = $this->config->apiUrl.'takeBetLogs';

        $result = $this->doSendProcess($params, $url);

        if ($result->code !== 0) {
            throw new SyncException(get_class($this), 'sync report error! error code : ' . $result->code . '. ' . $result->msg);
        }

        $rows = $result->data->bets;
        $total = $result->data->total;

        $data = [];

        foreach($rows as $row)
        {
            $data[] = $this->makeSyncCallBackParameter($row);
        }

        $params['page'] = $params['page'] + 1;
        if ($total > $params['page'] * $params['size']) {
            $data = array_merge($data, $this->doSyncReport($params));
        }

        return $data;

    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->gameCode = $row->gameId;
        $callBackParam->username = $row->account;
        $callBackParam->mid = $row->roundId;
        $callBackParam->round = $row->fieldId;
        $callBackParam->table = $row->tableId;
        $callBackParam->betAmount = $row->bet;
        $callBackParam->validAmount = $row->validBet;
        $callBackParam->winAmount = $row->lose + $row->bet;
        // $callBackParam->winAmount = $row->win;
        // $callBackParam->tip = $row->fee;
        $callBackParam->betAt = date('Y-m-d H:i:s', strtotime($row->roundBeginTime));
        $callBackParam->reportAt = date('Y-m-d H:i:s', strtotime($row->roundEndTime)); // 結算時間
        $callBackParam->status = Report::STATUS_COMPLETED;

        return $callBackParam;
    }

    private function doSendProcess($params, $url, $isFetch = false)
    {
        $fullParams = json_encode($params);

        $sign = md5($fullParams.$this->config->key);

        $this->curlHeader = ['Content-Type:application/json;charset=utf-8', 'Authorization: '.$sign];

        $response = $this->post($url, $fullParams, false);

        $result = json_decode($response);

        // 如果解不開，就直接把錯誤丟回去
        if($result === null)
        {
            throw new JSONException(get_class($this), 'error on JSON decode !', $result);
        }

        return $result;
    }

}
