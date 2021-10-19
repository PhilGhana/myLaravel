<?php

namespace MultiWallet\Api;

use MultiWallet\Api\Config\LisinConfigConstract;

use MultiWallet\Base\BaseMultiWalletInterface;
use MultiWallet\Params\MemberParameter;
use MultiWallet\Params\TransferParameter;
use MultiWallet\Params\LaunchGameParameter;
use MultiWallet\Params\SyncReportParameters;
use MultiWallet\Params\SyncCallBackParameter;

use MultiWallet\Feedback\MemberFeedback;
use MultiWallet\Feedback\TransferFeedback;
use MultiWallet\Feedback\BalanceFeedback;
use MultiWallet\Feedback\LaunchGameFeedback;
use MultiWallet\Feedback\SyncCallBackFeedback;

use MultiWallet\Exceptions\LoginException;
use MultiWallet\Exceptions\GameListException;

use App\Models\Report;

class Lisin extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $token = null;

    protected $refresh_token = null;

    protected $errorMessage = [
        '10001' => '驗證訊息不合法',
        '10002' => 'refresh token 無效',
        '10003' => '線路已凍結',
        '10004' => '線路已停用',
        '10005' => '請求指定的用戶 Id 不存在',
        '10006' => '重複的 user Id',
        '30001' => '扣除的金額超過利信系統餘額',
        '30002' => '增加的金額超過請求用戶的餘額',
        '40001' => '請求參數不合法',
    ];

    function __construct(array $config)
    {
        $this->config = new LisinConfigConstract();
        $this->config->balk = $config['balk'];

    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $token = $this->login();
        array_push($this->curlHeader, "Authorization:Bearer ".$token);

        $params = [
            'loginId'       => $member->username,
            'balk'          => $this->config->balk,
            'initialCredit' => $member->initialCredit,
            'maxProfit'     => 0,
            'nick'          => $member->nickname,
            'gameId'        => 1,
            'isTrail'       => false,
        ];

        $memberFeedback = new MemberFeedback();

        // $fullParams = $this->setParams($params);
        $response = $this->post("/partner/exportUser", $params, false);
        $result = json_decode($response);

        if($result instanceof MemberFeedback)
        {
            return $result;
        }

        if($result->code === 0)
        {
            $memberFeedback->extendParam = $result->data->userId;

            return $memberFeedback;
        }

        $memberFeedback->error_code = $result->code;
        $memberFeedback->error_msg = $this->errorMessage[$result->code];

        return $memberFeedback;
    }

    /**
     * 取得會員餘額
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        //token 要包進Authorization Header
        $token = $this->login();
        array_push($this->curlHeader, "Authorization:Bearer ".$token);
        // $loginId = $member->playerId;

        $params = [
            'loginId' => $member->username,
        ];

        $balanceFeedback = new BalanceFeedback();

        // $fullParams = $this->setParams($params);
        $response = $this->get("/partner/getPlayerBalance", $params, false);
        $result = json_decode($response);

        if($result->code === 0)
        {
            // $balanceFeedback->respcode;
            $balanceFeedback->result_code = $result->code;
            $balanceFeedback->balance = $result->data->balance;
            return $balanceFeedback;
        }
        //發生錯誤
        $balanceFeedback->error_code = $result->code;
        $balanceFeedback->error_msg = $this->errorMessage[$result->code];

        return $balanceFeedback;

    }

    /**
     * 存款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $token = $this->login();
        array_push($this->curlHeader, "Authorization:Bearer ".$token);

        $params = [
            'amount' =>  $transfer->amount,
        ];
        $playerId = $transfer->member->playerId;//這是要放進path

        $transferFeedback = new TransferFeedback();

        // $fullParams = $this->setParams($params);
        $response = $this->post("/partner/".$playerId."/updatePlayerBalance", $params, false);
        $result = json_decode($response);

        if($result->code === 0)
        {
            $moneyData = $result->data;

            $transferFeedback->balance = $moneyData->balance;
            $transferFeedback->response_code = $result->code;

            return $transferFeedback;
        }

         // 發生錯誤
         $transferFeedback->error_code = $result->code;
         $transferFeedback->error_msg = $this->errorMessage[$result->code];

         return $transferFeedback;
    }

    /**
     * 提款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $token = $this->login();
        array_push($this->curlHeader, "Authorization:Bearer ".$token);

        $params = [
            'amount' =>  -($transfer->amount),
        ];
        $playerId = $transfer->member->playerId;//這是要放進path

        $transferFeedback = new TransferFeedback();

        // $fullParams = $this->setParams($params);
        $response = $this->post("/partner/".$playerId."/updatePlayerBalance", $params, false);
        $result = json_decode($response);

        //如果正確
        if($result->code === 0)
        {
            $moneyData = $result->data;

            $transferFeedback->balance = $moneyData->balance;
            $transferFeedback->response_code = $result->code;

            return $transferFeedback;
        }

        // 發生錯誤
        $transferFeedback->error_code = $result->code;
        $transferFeedback->error_msg = $this->errorMessage[$result->code];

        return $transferFeedback;
    }

    /**
     * 獲取玩家進入遊戲信息
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $token = $this->login();
        array_push($this->curlHeader, "Authorization:Bearer ".$token);

        $params = [
            'loginId' => $launchGameParams->member->playerd,
            'gameId'  => $launchGameParams->gameId,
        ];

        $launchGameFeedback = new LaunchGameFeedback();

        // $fullParams = $this->setParams($params);
        $response = $this->get("/partner/getPlayerTicket", $params, false);
        $result = json_decode($response);

        if($result->code === 0)
        {
            $launchGameFeedback->gameUrl = $result->data->gameUrl;
            $launchGameFeedback->response_code = $result->code;

            return $launchGameFeedback;
        }

        // 發生錯誤
        $launchGameFeedback->error_code = $result->code;
        $launchGameFeedback->error_msg = $this->errorMessage[$result->code];

        return $launchGameFeedback;
    }

    //認證

    /**
     * 取得用戶驗證信息, ex. client_id or client_secret
     * 驗證信息默認失效期為 30 日，為提高雙方交易安全性，請務必定期重新獲取。
     *
     * @return object $credential
     */
    public function getCredential()
    {

        $credential = [];
        $result = $this->get("/partner/getCredential","",false);
        $data = $result->data;
        $credential->client_id = $data->client_id;
        $credential->client_secret = $data->client_secret;

        return $credential;
    }

    /**
     * 獲取api授權憑證
     * 獲取的授權憑證有時效性，請依照 expires_in (以分鐘為單位)，使用 refresh token 重新請求以更新憑證。
     *
     * @param string $client_id
     * @param string $client_secret
     * @param string $refresh_token
     * @return object $tokenResponse
     */
    public function getAuthToken($client_id,$client_secret,$refresh_token = null)
    {

        if($refresh_token == null)
        {
            $params = [
                'grant_type' => 'client_credentials',
                'client_id' => $client_id,
                'client_secret' => $client_secret,
            ];
        } else {
            $params = [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token
            ];
        }

        // $fullParams = $this->setParams($params);
        $tokenResponse = $this->post("/partner/getAuthToken", $params, false);
        $result = json_decode($tokenResponse);
        return $result;

    }

    /**
     * 同步注單
     *
     * @return void
     */
    public function syncReport(SyncReportParameters $srp, callable $callback)
    {
        $token = $this->login();
        array_push($this->curlHeader, "Authorization:Bearer ".$token);

        $params = [
            'pageIdx'      => 0,
            'pageSize'     => 10,
            'gameId'       => null,//指定遊戲 Id，空值代表全部遊戲
            'status'       => $srp->status,
            'startDate'    => $srp->startAt,
            'endDate'      => $srp->endAt,
            'startRound'   => null,
            'endRound'     => null,
            'queryLoginId' => null,
        ];

        $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
        $callBackFeedback = new SyncCallBackFeedback();

        // $fullParams = $this->setParams($params);
        $response = $this->get("/partner/ledgerQuery", $params, false);
        $result = json_decode($response);

        if($result->code === 0)
        {
            $rows = $result->data->items;

            $data = [];

            foreach($rows as $row)
            {
                $data[] = $this->makeSyncCallBackParameter($row);
            }

            if($result->data->total > $params['pageIdx'])
            {
                $params['pageIdx'] = $params['pageIdx'] + 1;
                $data = array_merge($data, $this->doSyncReport($params));
            }

            return $data;
        }

        $callBackFeedback->error_code = $result->code;
        $callBackFeedback->error_msg = $this->errorMessage[$result->code];

        return $callBackFeedback;
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid = $row->id;
        $callBackParam->username = $row->loginId;//只有提供會員登入ID
        $callBackParam->betAmount = $row->money;
        $callBackParam->validAmount = $row->money;//無提供有效金額,這裡帶入下注金額
        $callBackParam->gameCode = $row->slot->gameId;
        $callBackParam->winAmount = $row->result;
        $callBackParam->betAt = $row->created;
        $callBackParam->reportAt = $row->settled;
        // $callBackParam->ip = $row->ip;//沒有提供

        // $callBackParam->content = $row->wagerdetail->betonname;//無 wagerdetail?

        $callBackParam->status = $row->status;

        return $callBackParam;
    }

    /**
     * 登入
     *
     * @param void
     * @return string //token
     */
    public function login()
    {
        //不重覆登入,依過期時間規定,定期使用 'refresh token' 獲取新的憑證
        if($this->token !== null)
        {

            $tokenResponse = $this->getAuthToken(null, null, $this->refresh_token);

                if($tokenResponse->code === 0){
                    $this->token = $tokenResponse->data->access_token;
                    $this->refresh_token = $tokenResponse->data->refresh_token;
                    return $this->token;
                }

                throw new LoginException('Lisin server side login error!');
        }

        //取得驗證訊息 client_id, client_secret
        $credentialResult = $this->getCredential();
        $client_id = $credentialResult->client_id;
        $client_secret = $credentialResult->client_secret;

        //取得憑證 access_token
        $tokenResponse = $this->getAuthToken($client_id, $client_secret, null);
        if($tokenResponse->code === 0){
            $this->token = $tokenResponse->data->access_token;
            $this->refresh_token = $tokenResponse->data->refresh_token;
            return $this->token;
        }

        throw new LoginException('Lisin server side login error!');
    }

    private function setParams($params)
    {
        $params = [
            'Request' => $params,
        ];

        return json_encode($params);
    }
}
