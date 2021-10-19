<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Report;
use Carbon\Carbon;
use Exception;
use GameProvider\Exceptions\AesException;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\CreateMemberException;
use GameProvider\Exceptions\CurlException;
use GameProvider\Exceptions\GameListException;
use GameProvider\Exceptions\JSONException;
use GameProvider\Exceptions\LaunchGameException;
use GameProvider\Exceptions\LoginException;
use GameProvider\Exceptions\SyncException;
use GameProvider\Exceptions\TransferException;
use GameProvider\Operator\BaseApi;
use GameProvider\Operator\Feedback\BalanceFeedback;
use GameProvider\Operator\Feedback\LaunchGameFeedback;
use GameProvider\Operator\Feedback\MemberFeedback;
use GameProvider\Operator\Feedback\TransferFeedback;
use GameProvider\Operator\Multi\BaseMultiWalletInterface;
use GameProvider\Operator\Multi\Config\RTGConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
// use MultiWallet\Feedback\SyncCallBackFeedback;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;

class RTG extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;
    protected $token;
    protected $apiInfo;

    public function __construct(array $config)
    {
        $this->config           = new RTGConfigConstract();
        $this->config->apiUrl   = \str_finish($config['apiUrl'], '/');
        $this->config->agid     = $config['agid'];
        $this->config->username = $config['username'];
        $this->config->password = $config['password'];
        $this->config->currency = $config['currency'];
        $this->config->lang     = $config['lang'];
        $this->token            = null;
        $this->apiInfo          = null;
    }

    /**
     * 獲取遊戲列表.
     *
     * @return void
     */
    public function getGameList()
    {
        // $this->getToken();
        // $this->getApiInfo();

        // if (is_null($this->apiInfo)) {
        //     throw new LaunchGameException(get_class($this), 'fail to get game list');
        // }

        // $list = [];
        // foreach ($this->apiInfo['locales'] as $locale) {
        //     $result = $this->doSendProcess(
        //         [],
        //         'api/gamestrings?locale=' . $locale . '&orderBy=ManualOrder',
        //         'get',
        //         true
        //     );
        //     $list = array_merge($list, $result);
        // }

        // return $list;
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
            'agentId'   => $this->config->agid,
            'username'  => $member->playerId,
            'name'      => $member->playerId,
            'firstName' => $member->playerId,
            'lastName'  => 'fake',
            'email'     => $member->playerId.'@value.cc',
            'gender'    => 'male',
            'birthdate' => '1980-01-01T23:00',
            'currency'  => $this->config->currency,
        ];

        $this->getToken();
        $result = $this->doSendProcess($params, 'api/player', 'put');

        if (isset($result->errorCode)) {
            throw new CreateMemberException(get_class($this), 'create member error! error code : '.$result->errorCode, $result->message);
        }

        $memberFeedback              = new MemberFeedback();
        // 這寫法正確 他會把這個欄位存到username 參考下面
        $memberFeedback->extendParam = $result->id;

        return $memberFeedback;
    }

    /**
     * 存款.
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        // 只傳會用到的就好了
        $params = [
            'playerId'      => $transfer->member->username, // 如果使用playerId作为参数，则不需要其他兩個
            // 'agentId'       => $this->config->agid,
            // 'playerLogin'   => null, // 玩家登陆
            // 'trackingOne'   => $transfer->serialNo, // 追踪信息。例如第三方的交易记录ID
            // 'trackingTwo'   => null,
            // 'trackingThree' => null,
            // 'trackingFour'  => null,
        ];

        $this->getToken();
        $result = $this->doSendProcess($params, 'api/wallet/deposit/'.$transfer->amount);

        if (! isset($result->errorCode) || (bool) $result->errorCode) {
            throw new TransferException(
                get_class($this),
                $result->errorMessage.' error code : '.$result->errorCode,
                $result->errorMessage);
        }

        $transferFeedback               = new TransferFeedback();
        $transferFeedback->remote_payno = $result->transactionId;
        $transferFeedback->balance      = $result->currentBalance;

        return $transferFeedback;
    }

    /**
     * 提款.
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $params = [
            'playerId'      => $transfer->member->username, // 如果使用playerId作为参数，则不需要其他兩個
            // 'agentId'       => $this->config->agid,
            // 'playerLogin'   => null, // 玩家登陆
            // 'trackingOne'   => $transfer->serialNo, // 追踪信息。例如第三方的交易记录ID
            // 'trackingTwo'   => null,
            // 'trackingThree' => null,
            // 'trackingFour'  => null,
        ];

        $this->getToken();
        $result = $this->doSendProcess($params, 'api/wallet/withdraw/'.$transfer->amount);

        if (! isset($result->errorCode) || (bool) $result->errorCode) {
            throw new TransferException(
                get_class($this),
                $result->errorMessage.' error code : '.$result->errorCode,
                $result->errorMessage);
        }

        $transferFeedback               = new TransferFeedback();
        $transferFeedback->remote_payno = $result->transactionId;
        $transferFeedback->balance      = $result->currentBalance;

        return $transferFeedback;
    }

    /**
     * 會員登入（取得遊戲路徑）.
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $params = [
            'player'    => [
                'playerId'    => $launchGameParams->member->username, // 如果使用playerId作为参数，则不需要其他兩個
                // 'agentId'     => $this->config->agid,
                // 'playerLogin' => null, // 玩家登陆
            ],
            'gameId'    => $launchGameParams->gameId,
            'locale'    => $this->config->lang,
            'returnUrl' => '', //  URL 将于退出游戏时开启或重導
            'isDemo'    => $launchGameParams->fun ?? false, // 如果设置为false，它将以真钱模式启动
            // 'width'     => null,
            // 'height'    => null,
        ];

        $this->getToken();
        // 網址是這樣嗎? 亂寫
        // $result = $this->doSendProcess($params, 'api/player');
        $result = $this->doSendProcess($params, 'api/GameLauncher');

        if (isset($result->errorCode)) {
            throw new LaunchGameException(get_class($this), 'launch game error! error code : '.$result->errorCode, $result->message);
        }

        $launchGameFeedback                = new LaunchGameFeedback();
        $launchGameFeedback->gameUrl       = $result->instantPlayUrl;
        $launchGameFeedback->mobileGameUrl = $result->instantPlayUrl;
        $launchGameFeedback->token         = $result->token;

        return $launchGameFeedback;
    }

    /**
     * 取得會員餘額.
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        $params = [
            'playerId'    => $member->username, // 如果使用playerId作为参数，则不需要其他兩個
            // 'agentId'     => $this->config->agid,
            // 'playerLogin' => null, // 玩家登陆
        ];

        $this->getToken();
        $result = $this->doSendProcess($params, 'api/wallet');

        if (isset($result->errorCode)) {
            throw new BalanceException(get_class($this), 'get balance error! error code : '.$result->errorCode, $result->message);
        }

        $balanceFeedback          = new BalanceFeedback();
        $balanceFeedback->balance = $result;

        return $balanceFeedback;
    }

    /**
     * 同步注單(取回時間區段的所有注單).
     *
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        // 注意: 遊戲商有註明 他們使用UTC時間
        $start = new \DateTime($srp->startAt, new \DateTimeZone('Asia/Taipei'));
        $end   = new \DateTime($srp->endAt, new \DateTimeZone('Asia/Taipei'));

        $params = [
            'params' => [
                'agentId'   => $this->config->agid,
                'fromDate'  => Carbon::createFromTimestamp($start->format('U'))->setTimezone('UTC')->toIso8601ZuluString(),
                'toDate'    => Carbon::createFromTimestamp($end->format('U'))->setTimezone('UTC')->toIso8601ZuluString(),
            ],
            'pageIndex' => 0,
            'pageSize'  => 1000,
        ];

        return $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
        $this->getToken();
        $result = $this->doSendProcess($params, 'api/report/playergame');
        $items  = $result->items;
        $data   = [];

        // 總頁數
        $totalPage  = ceil($result->totalCount / 1000);

        // 這種寫法只支援一頁 修正為新的
        foreach ($items as $item) {
            // array_push($data, $this->makeSyncCallBackParameter($item));
            $data[] = $this->makeSyncCallBackParameter($item);
        }

        if ($totalPage > $params['pageIndex']) {
            $params['pageIndex'] = $params['pageIndex'] + 1;
            $data                = array_merge($data, $this->doSyncReport($params));
        }

        return $data;
    }

    private function makeSyncCallBackParameter($row)
    {
        $format = 'Y-m-d H:i:s';
        $now    = date($format);

        $callBackParam              = new SyncCallBackParameter();
        // 是在哪裡看到有回傳ID?
        // $callBackParam->mid         = $row->id;
        // 這裡因為不清楚怎麼回事, 確保單一
        $callBackParam->mid         = $row->playerName.$row->gameNumber;
        // 這裡要比對我們的會員platerId 不可能是去他們的系統ID阿
        // $callBackParam->username    = $row->casinoPlayerId; // 玩家的系统ID
        $callBackParam->username    = $row->playerName;
        $callBackParam->betAmount   = $row->bet;
        $callBackParam->validAmount = $row->bet;
        $callBackParam->gameCode    = $row->gameId;
        $callBackParam->winAmount   = $row->win;
        $callBackParam->betAt       = localeDatetime($row->gameStartDate)->format($format); // 遊戲開始日期UTC + 0
        $callBackParam->reportAt    = localeDatetime($row->gameStartDate)->format($format);
        $callBackParam->ip          = '';
        $callBackParam->status      = Report::STATUS_COMPLETED;

        return $callBackParam;
    }

    /**
     * fetch token.
     */
    private function getToken()
    {
        if (! \is_null($this->token)) {
            return;
        }

        // $url    = $this->config->apiUrl . 'api/start/token';
        $params = [
            'username' => $this->config->username,
            'password' => $this->config->password,
        ];
        $response = $this->doSendProcess($params, 'api/start/token', 'get');

        if (\is_null($response) || isset($response->error)) {
            $msg = $response->error ?? '';
            throw new Exception(get_class($this), 'error get token', $msg);
        }

        $this->token = $response->token;
    }

    /**
     * POST WITH TOKEN.
     */
    private function doSendProcess($params, $api, $method = 'post', $needArray = false)
    {
        $url        = $this->config->apiUrl.$api;

        $curlHeader = [
            'Content-Type: application/json',
            'Authorization:'.$this->token,
        ];

        if ($method == 'get') {
            $params = http_build_query($params);
            $url    = $url.'?'.$params;

            if (strpos($url, 'start/token')) {
                // 如果是取token 拔掉Authorization
                unset($curlHeader[1]);
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeader);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        if ($method === 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        if ($method === 'put') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $result      = curl_exec($ch);
        $reponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // 如果對方發生錯誤，直接報錯，不處理
        if ($reponseCode !== 200) {
            // TODO : 這邊要寫到log
            if ($reponseCode !== 201) {
                throw new CurlException(get_class($this), 'curl error : '.$url.' '.$reponseCode, json_encode($params));
            }
        }

        curl_close($ch);
        $result = json_decode($result, $needArray);

        // 如果解不開，就直接把錯誤丟回去
        if ($result === null) {
            throw new JSONException(get_class($this), 'error on JSON decode !', $result);
        }

        return $result;
    }

    /**
     * 玩家的货币
     *
     * @param string $country // 使用者國籍代碼
     */
    private function getcurrency($country)
    {
        switch ($country) {
            case 'China':
                return 'CNY';
                break;

            default:
                return 'CNY';
                break;
        }
    }

    /*
     * fetch api info
     */
    // private function getApiInfo()
    // {
    //     if (\is_null($this->token)) {
    //         throw new AesException(get_class($this), 'empty token when get user info');
    //     }

    //     $this->getToken();
    //     $result        = $this->doSendProcess([], 'api/start', 'get');
    //     $this->apiInfo = $result->casinos;
    // }
}
