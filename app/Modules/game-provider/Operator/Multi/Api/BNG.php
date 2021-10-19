<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Game;
use App\Models\Report;
use Exception;
use GameProvider\Exceptions\AesException;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\CreateMemberException;
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
use GameProvider\Operator\Multi\Config\BNGConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
// use MultiWallet\Feedback\SyncCallBackFeedback;

use GameProvider\Operator\Params\TransferParameter;

class BNG extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $token = null;

    protected $errorMessage = [
        'http_status_400'        => '傳入參數轉遞不正確。響應主體將包含錯誤信息。',
        'http_status_403'        => 'API金鑰無效或不存在。',
        'http_status_404'        => '在服務器上無此指令。',
        'http_status_500'        => '服務器錯誤。',
        'http_status_503'        => '暫時無法使用。',
        'MALFORMED_REQUEST'      => '無效的JSON請求。',
        'PLAYER_ID_MISSED'       => '請求中缺少 player_id 參數。',
        'PLAYER_ID_NOT_VALID'    => 'player_id 參數包含不支持的符號。',
        'CURRENCY_MISSED'        => '請求中缺少 currency 參數。',
        'CURRENCY_NOT_SUPPORTED' => '該貨幣不支持。',
        'API_INACTIVE'           => 'API不支援該專案。',
        'IS_TEST_MISMATCH'       => '與玩家當前 is_test 狀態不符合。',
        'IS_TEST_NOT_VALID'      => 'is_test 數據類型無效。',
        'MODE_NOT_VALID'         => '無效的遊戲模式。',
        'BRAND_NOT_VALID'        => 'brand 參數包含不支持的符號。',
        'MALFORMED_REQUEST'      => '無效的JSON請求。',
        'PLAYER_NOT_FOUND'       => '在BNG的數據庫里該玩家的賬戶不存在。',
        'UID_MISSED'             => '請求中缺少 uid 參數。',
        'UID_NOT_VALID'          => 'uid 參數包含不支持的符號。',
        'AMOUNT_MISSED'          => '請求中缺少 amount 參數。',
        'AMOUNT_NOT_VALID'       => 'amount 參數不是整數或少於0。',
        'TYPE_MISSED'            => '請求中缺少 type 參數。',
        'TYPE_NOT_VALID'         => 'type 參數是無效的。',
        'DUPLICATE_UID'          => '包含相同的 uid 但不同參數的請求已經被處理了。如果新的請求完全複制了從以前的請求之一(相同的uid和所有參數)，BNG的服務器響應成功的結果。',
        'INSUFFICIENT_FUNDS'     => '玩家的余額不足，只用在 debit 類型。',
        'BRAND_REQUIRED'         => '您已在不同 站點 上傳遞過此 player_id。',
    ];

    public function __construct(array $config)
    {
        $this->config = new BNGConfigConstract();

        $this->config->apiUrl   = $config['apiUrl'];
        $this->config->apiToken = $config['apiToken'];
        $this->config->currency = $config['currency'];
        $this->config->wl       = $config['wl'];
        $this->config->tz       = $config['tz'];
        // $this->config->mode     = $config['mode'];
        // $this->config->is_test  = $config['is_test'];
        // $this->config->brand    = $config['brand'];
        $this->doStuck = false;
    }

    /**
     * 獲取遊戲列表.
     *
     * @return void
     */
    public function getGameList()
    {
        $apiUrl = $this->config->apiUrl.'/api/v1/game/list';

        $params = [
            'api_token' => $this->config->apiToken,
        ];

        $result = $this->doSendProcess(null, $apiUrl, $params);

        // $items = $result->items;
        // $i = 10;
        // // 寫進去資料庫
        // foreach($items as $row)
        // {
        //     $game = new Game();
        //     $game->platform_id = 2;
        //     $game->type = 'slot';
        //     $game->code = $row->game_id;
        //     $game->code_mobile = $row->game_id;
        //     $game->name_en = $row->i18n->en->title;
        //     $game->name_zh_tw = $row->i18n->{'zh-hant'}->title;
        //     $game->name_zh_cn = $row->i18n->zh->title;
        //     $game->launch_method = 'GET';
        //     $game->enabled = 1;
        //     $game->maintain = 0;
        //     $game->image = $row->i18n->{'zh-hant'}->banner_path;
        //     $game->order = $i;

        //     $game->save();
        //     $i = $i + 10;
        // }

        return $result;
    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $apiUrl = $this->config->apiUrl."/wallet/{$this->config->wl}/create_player";

        $is_test = false;
        $mode    = 'REAL';

        $params = [
            'api_token' => $this->config->apiToken,
            'player_id' => $member->playerId,
            'currency'  => $this->config->currency,
            'mode'      => $mode,
            'is_test'   => $is_test,
            // "brand"     => $this->config->brand,
        ];

        $memberFeedback = new MemberFeedback();

        $result = $this->doSendProcess($memberFeedback, $apiUrl, $params);

        return $memberFeedback;
    }

    /**
     * 存款
     * 將中心錢包轉到遊戲平台.
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $apiUrl = $this->config->apiUrl."/wallet/{$this->config->wl}/transfer_balance";

        $mode = 'REAL';

        $params = [
            'api_token' => $this->config->apiToken,
            'player_id' => $transfer->member->playerId,
            // "brand"     => $this->config->brand,
            'currency'  => $this->config->currency,
            'mode'      => $mode,
            'uid'       => md5($transfer->member->playerId.time()),
            'amount'    => (string) $transfer->amount,
            'type'      => 'CREDIT',
        ];

        $transferFeedback = new TransferFeedback();

        try {
            $result = $this->doSendProcess($transferFeedback, $apiUrl, $params);

            $transferFeedback->balance      = $result->balance_after;
            $transferFeedback->remote_payno = $result->uid;
            $transferFeedback->uid          = $result->uid;

            return $transferFeedback;
        } catch (Exception $e) {
            throw new TransferException(
                get_class($this),
                $e->getMessage(),
                $e->getMessage(),
                $this->doStuck
            );
        }
    }

    /**
     * 提款.
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $apiUrl = $this->config->apiUrl."/wallet/{$this->config->wl}/transfer_balance";

        $mode = 'REAL';

        $params = [
            'api_token' => $this->config->apiToken,
            'player_id' => $transfer->member->playerId,
            // "brand"     => $this->config->brand,
            'currency'  => $this->config->currency,
            'mode'      => $mode,
            'uid'       => md5($transfer->member->playerId.time()),
            'amount'    => (string) $transfer->amount,
            'type'      => 'DEBIT',
        ];

        $transferFeedback = new TransferFeedback();

        try {
            $result = $this->doSendProcess($transferFeedback, $apiUrl, $params);

            $transferFeedback->balance      = $result->balance_after;
            $transferFeedback->remote_payno = $result->uid;
            $transferFeedback->uid          = $result->uid;

            return $transferFeedback;
        } catch (Exception $e) {
            throw new TransferException(
                get_class($this),
                $e->getMessage(),
                $e->getMessage(),
                $this->doStuck
            );
        }
    }

    public function getToken(MemberParameter $member)
    {
        $apiUrl = $this->config->apiUrl."/wallet/{$this->config->wl}/get_player_token";

        $mode = 'REAL';

        $params = [
            'api_token' => $this->config->apiToken,
            'player_id' => $member->playerId,
            'currency'  => $this->config->currency,
            'mode'      => $mode,
        ];

        $memberFeedback = new MemberFeedback();

        $result = $this->doSendProcess($memberFeedback, $apiUrl, $params);

        return $result->player_token;
    }

    /**
     * 會員登入（取得遊戲路徑）.
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $apiUrl = $this->config->apiUrl.'/game.html?';

        $platform = 'desktop';
        if ($launchGameParams->device === 'mobile') {
            $platform = 'mobile';
        }

        $token = $this->getToken($launchGameParams->member);

        $params = [
            'token='.$token,
            'game='.(int) $launchGameParams->gameId,
            'ts='.time(),
            'platform='.$platform,
            'wl='.$this->config->wl,
            'lang='.$launchGameParams->lang,
            'tz='.$this->config->tz,
        ];

        $launchGameFeedback = new LaunchGameFeedback();

        $launchGameFeedback->gameUrl = $apiUrl.implode('&', $params);

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
        $apiUrl = $this->config->apiUrl."/wallet/{$this->config->wl}/get_player";

        $mode = 'REAL';

        $params = [
            'api_token' => $this->config->apiToken,
            'player_id' => $member->playerId,
            // "brand"     => $this->config->brand,
            'currency'  => $this->config->currency,
            'mode'      => $mode,
        ];

        $balanceFeedback = new BalanceFeedback();

        $result = $this->doSendProcess($balanceFeedback, $apiUrl, $params);

        $balanceFeedback->balance = $result->balance;

        return $balanceFeedback;
    }

    /**
     * 同步注單(取回時間區段的所有注單).
     *
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback, $fetch_state = '')
    {
        $params = [
            'api_token'   => $this->config->apiToken,
            'start_date'  => $srp->startAt,
            'end_date'    => $srp->endAt,
            'fetch_state' => $fetch_state,
        ];

        return $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
        $apiUrl = $this->config->apiUrl.'/api/v1/transaction/list/';

        $result = $this->doSendProcess(null, $apiUrl, $params);

        $items = $result->items;

        $data = [];

        foreach ($items as $item) {
            $data[] = $this->makeSyncCallBackParameter($item);
        }

        // 檢查還有沒有頁數
        if ($result->fetch_state != null) {
            $params['fetch_state'] = $result->fetch_state;

            $data = array_merge($data, $this->doSyncReport($params));
        }

        return $data;
    }

    private function makeSyncCallBackParameter($row)
    {
        $format = 'Y-m-d H:i:s';
        // $now   = date($format);

        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid         = $row->transaction_id;
        $callBackParam->username    = $row->player_id;
        $callBackParam->betAmount   = $row->bet;
        $callBackParam->validAmount = $row->bet;
        $callBackParam->gameCode    = $row->game_id;
        $callBackParam->winAmount   = $row->win ?? 0;
        $callBackParam->betAt       = localeDatetime($row->c_at)->format($format);
        $callBackParam->reportAt    = localeDatetime($row->c_at)->format($format);
        $callBackParam->ip          = '';
        $callBackParam->round       = $row->round_id;
        $callBackParam->content     = '';

        $callBackParam->status = Report::STATUS_COMPLETED;

        return $callBackParam;
    }

    private function doSendProcess($feedback, $apiUrl, $params)
    {
        $response = $this->post($apiUrl, json_encode($params), true);
        // $result   = $response['body'];

        // if ($this->reponseCode != 200) {
        //     $feedback->response_code = $this->reponseCode;

        //     if ($this->reponseCode == 400) {
        //         throw new SyncException(get_class($this), 'sync error! error code : ' . $result['error'], $this->errorMessage[$result['error']]);
        //     } else {
        //         throw new SyncException(get_class($this), 'sync error! error code : ' . $result['error'], $this->errorMessage['http_status_' . $this->reponseCode]);
        //     }
        // }

        return $response;
    }
}
