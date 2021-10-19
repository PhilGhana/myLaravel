<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Game;
use App\Models\Report;
use Carbon\Carbon;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\CreateMemberException;
use GameProvider\Exceptions\GameListException;
use GameProvider\Exceptions\LoginException;
use GameProvider\Exceptions\SyncException;
use GameProvider\Exceptions\TransferException;
// use GameProvider\Exceptions\AesException;
use GameProvider\Operator\BaseApi;
use GameProvider\Operator\Feedback\BalanceFeedback;
// use GameProvider\Exceptions\JSONException;
use GameProvider\Operator\Feedback\LaunchGameFeedback;
use GameProvider\Operator\Feedback\MemberFeedback;
// use GameProvider\Exceptions\LaunchGameException;
use GameProvider\Operator\Feedback\TransferFeedback;
use GameProvider\Operator\Multi\BaseMultiWalletInterface;
use GameProvider\Operator\Multi\Config\ICONICConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
// use MultiWallet\Feedback\SyncCallBackFeedback;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;

class ICONIC extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $token = null;

    protected $gameUrl = '';

    public function __construct(array $config)
    {
        $this->config = new ICONICConfigConstract();

        $this->config->apiUrl     = $config['apiUrl'];
        $this->config->username   = $config['username'];
        $this->config->password   = $config['password'];
        $this->config->language   = $config['language'];
        $this->config->platformId = $config['platformId'];

        $this->gameUrl = $config['gameURL'];
    }

    public function login()
    {
        $result = $this->doSendProcess('/login', [
            'username' => $this->config->username,
            'password' => $this->config->password,
        ], true);

        if (isset($result->error)) {
            throw new LoginException(get_class($this), 'error when iconic login : '.$result->message);
        }

        $this->token = $result->token;
    }

    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $token      = $this->GUID();
        $lang       = $this->config->language;
        $gameId     = $launchGameParams->gameId;
        $platformId = $this->config->platformId;
        $gameUrl    = $this->gameUrl;

        // 要把TOKEN存起來 因為他們還會回來問一次 60秒過期
        redis()->hset('ICONIC'.$token, $token, $launchGameParams->member->playerId);
        redis()->expire('ICONIC'.$token, 60);

        $launchGameFeedback          = new LaunchGameFeedback();
        $launchGameFeedback->gameUrl = "$gameUrl/$gameId?platform=$platformId&token=$token&lang=$lang";

        return $launchGameFeedback;
    }

    public function getBalance(MemberParameter $member)
    {
        $this->login();

        $method = '/api/v1/players';

        $params = [
            'player' => $member->playerId,
        ];

        $balanceFeedback = new BalanceFeedback();

        $result = $this->doSendProcessGet($method, $params);

        if (isset($result->error)) {
            throw new BalanceException(get_class($this), 'error when iconic getBalance : '.$result->message);
        }

        $balanceFeedback->balance = (($result->data)[0]->balance) / 100;

        return $balanceFeedback;
    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $this->login();

        $method = '/api/v1/players';

        $params = [
            'username'  => $member->playerId,
        ];

        $memberFeedback = new MemberFeedback();

        $result = $this->doSendProcess($method, $params);

        if (isset($result->error)) {
            throw new CreateMemberException(get_class($this), 'error when iconic create member : '.$member->playerId.' : '.$result->message);
        }

        return $memberFeedback;
    }

    public function getGameList()
    {
        $this->login();

        $method = '/api/v1/games';

        $result = $this->doSendProcessGet($method, []);

        if (isset($result->error)) {
            throw new GameListException(get_class($this), 'error when iconic get game list : '.$result->message);
        }

        // 寫入資料庫
        // $data = $result->data;
        // foreach($data as $row)
        // {
        //     $game = new Game();
        //     $game->name_en = $row->name;
        //     $game->name_zh_cn = $row->name;
        //     $game->name_zh_tw = $row->name;
        //     $game->name_jp = $row->name;
        //     $game->image = $row->src->image_m;
        //     $game->code = $row->productId;
        //     $game->code_mobile = $row->productId;
        //     $game->launch_method = 'GET';
        //     $game->enabled = 1;
        //     $game->platform_id = 12;
        //     $game->type = $row->type;
        //     if($row->type == 'fish')
        //     {
        //         $game->type = 'fishing';
        //     }
        //     $game->save();
        // }

        return $result->data;
    }

    public function deposit(TransferParameter $transfer)
    {
        $this->login();

        $method = '/api/v1/players/deposit';

        $params = [
            'transactionId' => $this->GUID(),
            'amount'        => $transfer->amount * 100,
            'player'        => $transfer->member->playerId,
        ];

        $result = $this->doSendProcess($method, $params);

        if (isset($result->error)) {
            throw new TransferException(get_class($this), 'error when iconic deposit '.$transfer->member->playerId.' : '.$result->message);
        }

        $transferFeedback = new TransferFeedback();

        $transferFeedback->balance      = bcdiv((string) $result->data->balance, '100', 2);
        $transferFeedback->remote_payno = $result->data->transactionId;

        return $transferFeedback;
    }

    public function withdraw(TransferParameter $transfer)
    {
        $this->login();

        $method = '/api/v1/players/withdraw';

        $params = [
            'transactionId' => $this->GUID(),
            'amount'        => $transfer->amount * 100,
            'player'        => $transfer->member->playerId,
        ];

        $result = $this->doSendProcess($method, $params);

        if (isset($result->error)) {
            throw new TransferException(get_class($this), 'error when iconic withdraw '.$transfer->member->playerId.' : '.$result->message);
        }

        $transferFeedback = new TransferFeedback();

        $transferFeedback->balance      = bcdiv((string) $result->data->balance, '100', 2);
        $transferFeedback->remote_payno = $result->data->transactionId;

        return $transferFeedback;
    }

    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $this->login();

        $start = new \DateTime($srp->startAt, new \DateTimeZone('Asia/Taipei'));
        $end   = new \DateTime($srp->endAt, new \DateTimeZone('Asia/Taipei'));

        $params = [
            'start'        => $start->format('U').'000',
            'end'          => $end->format('U').'000',
            'pageSize'     => 10000,
            'page'         => 1,
        ];

        return $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
        $method    = '/api/v1/profile/rounds';
        $result    = $this->doSendProcessGet($method, $params);

        if (isset($result->error)) {
            throw new SyncException(get_class($this), 'error when iconic sync : '.$result->message);
        }

        $data      = $result->data;
        $finalData = [];

        foreach ($data as $row) {
            $finalData[] = $this->makeSyncCallBackParameter($row);
        }

        $totalSize = $result->totalSize;
        $pageSize  = $result->pageSize;
        $allPage   = ceil($totalSize / $pageSize);

        if ($allPage >= $result->page) {
            $params['page'] = $params['page'] + 1;
            $finalData      = array_merge($finalData, $this->doSyncReport($params));
        }

        return $finalData;
    }

    private function doSendProcess(string $method, array $params, bool $is_login = false)
    {
        $url = $this->config->apiUrl.$method;

        if (! $is_login) {
            // 帶入表頭金鑰
            $this->curlHeader[] = 'Authorization:Bearer '.$this->token;
        }

        $response = $this->post($url, json_encode($params), true);

        return $response;
    }

    private function doSendProcessGet(string $method, array $params)
    {
        $url = $this->config->apiUrl.$method.'?'.http_build_query($params);

        $this->curlHeader[] = 'Authorization:Bearer '.$this->token;

        $response = $this->get($url, json_encode($params), true);

        return $response;
    }

    private function makeSyncCallBackParameter($row)
    {
        $format = 'Y-m-d H:i:s';
        // $now    = date($format);
        $bet_at = Carbon::parse($row->createdAt)->addHours(8)->format('Y-m-d H:i:s');

        $callBackParam           = new SyncCallBackParameter();
        $callBackParam->mid      = $row->id; // 注單ID
        $callBackParam->gameCode = $row->productId;
        $callBackParam->username = $row->player; // "下注會員帳號
        $callBackParam->betAt    = $bet_at; // 下注時間
        $callBackParam->reportAt = $bet_at; // 結算時間
        // $callBackParam->table = ;
        // $callBackParam->round = $row->gid;
        $callBackParam->content  = $row->gameType;
        // $callBackParam->waterAmount = ;
        $callBackParam->betAmount   = bcdiv((string) $row->bet, '100', 2); // 下注時間金額
        $callBackParam->validAmount = bcdiv((string) $row->validBet, '100', 2); // 有效下注
        $callBackParam->winAmount   = bcdiv((string) $row->win, '100', 2); // 輸贏金額
        // $callBackParam->prize = ;
        // $callBackParam->tip = ;
        // $callBackParam->ip     = $row->orderIP; //下注IP

        $status = [
            'playing' => Report::STATUS_BETTING,
            'cancel'  => Report::STATUS_CANCEL,
            'finish'  => Report::STATUS_COMPLETED,
        ];

        $callBackParam->status = $status[$row->status];

        return $callBackParam;
    }
}
