<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\GamePlatform;
use App\Models\Game;
use GameProvider\Operator\BaseApi;

use GameProvider\Operator\Multi\Config\EVOConfigConstract;

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

class EVO extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $curlHeader = ['Content-Type:application/json;charset=utf-8'];


    function __construct(array $config)
    {
        $this->config = new EVOConfigConstract();

        $this->config->apiUrl = $config['apiUrl'];
        $this->config->casinoId = $config['casinoId'];
        $this->config->ua2Token = $config['ua2Token'];
        $this->config->ecToken = $config['ecToken'];
        $this->config->gameHistoryApiToken = $config['gameHistoryApiToken'];
        $this->config->externalLobbyApiToken = $config['externalLobbyApiToken'];

        $this->config->country = $config['country'];
        $this->config->lang = $config['lang'];
        $this->config->currency = $config['currency'];


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
        $entry = $this->doAuth($member);

        if (!$entry) {
            throw new CreateMemberException(get_class($this), 'create member error! no entry data');
        }

        $memberFeedback = new MemberFeedback();

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
        $params = [
            'cCode' => 'RWA',
            'ecID' => $this->config->casinoId.$this->config->ecToken,
            'euID' => $member->playerId,
            'output' => 1,
            'uID' => $member->playerId,
        ];

        $balanceFeedback = new BalanceFeedback();

        $result = $this->doCashRequest($params);

        if (!isset($result->result)) {
            throw new BalanceException(get_class($this), 'get balance error! wrong format : '.json_encode($result));
        }
        if ($result->result === 'Y') {
            $balanceFeedback->balance = $result->abalance; //Player's available balance (not including bonus)
            // $balanceFeedback->balance = $result->tbalance; // Player's total balance (including bonus)
            return $balanceFeedback;
        }

        throw new BalanceException(get_class($this), 'get balance error! error msg : ' . $result->errormsg);
    }

    /**
     * 會員登入（取得遊戲路徑）
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */

    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $entry = $this->doAuth($launchGameParams->member, $launchGameParams->gameId);

        if (!$entry) {
            throw new LaunchGameException(get_class($this), 'launch game error! no entry data');
        }

        $launchGameFeedback = new LaunchGameFeedback();

        $launchGameFeedback->gameUrl = $this->config->apiUrl . $entry;

        return $launchGameFeedback;
    }


    /**
     * 存款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $payno =  $transfer->member->playerId . time();

        $params = [
            'cCode' => 'ECR',
            'amount' => $transfer->amount,
            'ecID' => $this->config->casinoId.$this->config->ecToken,
            'eTransID' => $payno,
            'euID' => $transfer->member->playerId,
            'output' => 1,
            'uID' => $transfer->member->playerId,
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doCashRequest($params);

        if (!isset($result->result)) {
            throw new TransferException(get_class($this), 'deposit error! wrong format : '.json_encode($result));
        }
        if ($result->result === 'Y') {
            $transferFeedback->remote_payno = $result->transid;
            return $transferFeedback;
        }

        throw new TransferException(get_class($this), 'deposit error! error msg : ' . $result->errormsg);
    }

     /**
     * 提款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $payno =  $transfer->member->playerId . time();

        $params = [
            'cCode' => 'EDB',
            'amount' => $transfer->amount,
            'ecID' => $this->config->casinoId.$this->config->ecToken,
            'eTransID' => $payno,
            'euID' => $transfer->member->playerId,
            'output' => 1,
            'uID' => $transfer->member->playerId,
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doCashRequest($params);

        if (!isset($result->result)) {
            throw new TransferException(get_class($this), 'deposit error! wrong format : '.json_encode($result));
        }
        if ($result->result === 'Y') {
            $transferFeedback->remote_payno = $result->transid;
            return $transferFeedback;
        }

        throw new TransferException(get_class($this), 'deposit error! error msg : ' . $result->errormsg);
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

        $format = 'Y-m-d H:i:s';
        $startAt = Carbon::parse($srp->startAt)->addHours(-1 * Carbon::createFromTimestamp(0)->offsetHours)->format($format);
        $endAt = Carbon::parse($srp->endAt)->addHours(-1 * Carbon::createFromTimestamp(0)->offsetHours)->format($format);
        $startAt = str_replace(' ', 'T', $startAt).'Z';
        $endAt = str_replace(' ', 'T', $endAt).'Z';
        $params = [
            'startDate'     => $startAt,
            'endDate'       => $endAt
        ];
        return $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
        $url = $this->config->apiUrl . '/api/gamehistory/v1/casino/games';

        $result = $this->doSendProcess($params, $url);

        if (!isset($result->data)) {
            throw new SyncException(get_class($this), 'sync report error! error code : ' . $result->status);
        }

        $rows = $result->data;

        $data = [];

        foreach($rows as $row){
            foreach($row->games as $game) {
                foreach ($game->participants as $player) {
                    foreach ($player->bets as $bet) {
                        $data[] = $this->makeSyncCallBackParameter($game, $player,$bet);
                    }
                }
            }
        }

        $data = $this->doVaildCalculate($data);

        return $data;

    }

    private function makeSyncCallBackParameter($game, $player, $bet)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid = $bet->transactionId.'-'.$bet->code;

        $callBackParam->username = $player->playerId;

        $callBackParam->gameCode = $game->table->id;

        $callBackParam->settleAt = date('Y-m-d H:i:s', strtotime($game->settledAt));    // 結算時間
        $callBackParam->betAt    = date('Y-m-d H:i:s', strtotime($bet->placedOn));      // 下注開始時間
        $callBackParam->reportAt = date('Y-m-d H:i:s', strtotime($bet->placedOn));      // 下注開始時間


        $callBackParam->status = Report::STATUS_COMPLETED; //Resolved
        if ($game->status == 'Cancelled') {
            $callBackParam->status = Report::STATUS_CANCEL;
        }

        $callBackParam->betAmount = $bet->stake;
        $callBackParam->validAmount = $bet->stake;
        $callBackParam->winAmount = $bet->payout;

        $callBackParam->round = $game->id;
        $callBackParam->table = $bet->transactionId;
        $callBackParam->content = $bet->code;

        return $callBackParam;
    }

    /**
     * 進行會員登入
     */
    private function doAuth(MemberParameter $member, $gameCode = null) {

        $uuid =  $member->playerId . time();

        $url =  $this->config->apiUrl . '/ua/v1/' . $this->config->casinoId . '/' . $this->config->ua2Token;

        $isLive = false;

        if (!$gameCode) {
            $gameCode = 'SBCTable00000001';//創建帳號時預設遊戲
        } else {
            // 判斷是否為真人遊戲
            $platform = GamePlatform::select('id')
            ->where('key', 'EVO')
            ->first();

            if (!$platform) {
                throw new LoginException(get_class($this), 'auth error! platform id not found.');
            }

            $platformId = $platform['id'];

            $games = Game::select('type')
            ->where('code', $gameCode)
            ->where('platform_id', $platformId)
            ->first();

            if ($games && $games['type'] == 'live') {
                $isLive = true;
            }

        }

        $params = [
            'uuid'   => $uuid,
            'player' => [
                'id'     => $member->playerId,
                'update' => false,
                'country'=> $this->config->country,
                'language'=> $this->config->lang,
                'currency'=> $this->config->currency,
                'session' => [
                    'id' => $uuid,
                    'ip' => '127.0.0.1'
                    // 'ip' => $member->ip
                ]
            ],
            'config' => [
                'game' => [
                    'category' =>  $gameCode,
                    'table' =>  [
                        'id' => $gameCode
                    ]
                ],
                'channel' => [
                    'wrapped' => false
                ],
            ],
        ];

        if ($isLive) {
            unset($params['config']['game']);
        }

        $fullParams = json_encode($params);

        $response = $this->post($url , $fullParams);

        if (isset($response->errors)) {
            $errorMsgs = array();
            foreach ($response->errors as $error) {
                $errorMsgs[] = $error->code . ':'.$error->message;
            }
            throw new LoginException(get_class($this), 'auth error! ' . implode(", ",$errorMsgs));
        }

        if (!isset($response->entry)) {
            throw new LoginException(get_class($this), 'auth error! do not have entry. response:' . json_encode($response));
        }

        return $response->entry;

    }

    private function doSendProcess($params, $apiUrl)
    {

        $get_params = http_build_query($params);

        $this->curlHeader = ["Authorization: Basic ".base64_encode($this->config->casinoId.":". $this->config->gameHistoryApiToken)];

        $response = $this->get($apiUrl . '?' . $get_params, '', false);

        $result = json_decode($response);

        // 如果解不開，就直接把錯誤丟回去
        if($result === null)
        {
            throw new JSONException(get_class($this), 'error on JSON decode !', $result);
        }

        return $result;
    }

    private function doCashRequest($params)
    {
        $get_params = http_build_query($params);

        $response = $this->get($this->config->apiUrl . '/api/ecashier?' . $get_params, '', false);

        $result = $this->xml2js($response);

        // 如果解不開，就直接把錯誤丟回去
        if($result === null)
        {
            throw new JSONException(get_class($this), 'error on JSON decode !', $response);
        }

        return $result;
    }

    // xml 解析
    public function xml2js($xmlnode)
    {
        $xml = simplexml_load_string($xmlnode);
        $xml = json_encode($xml);
        $xml = json_decode($xml);

        return $xml;
    }

    /**
     * 處理對押排除 重新計算有效下注
     */
    private function doVaildCalculate($data)
    {
        // 先將本次所有注單分組
        $betGroup = $this->getBetGroup($data);

        // 獲取拿來驗證的遊戲組別
        $vaildGroup = $this->getValidGroup();
        // throw new JSONException(get_class($this), json_encode($betGroup));
        // throw new JSONException(get_class($this), json_encode($data));

        // 將各組別依據桌號取值
        foreach ($betGroup as $table => $roundData) {
            // 如果此桌存在於須驗證的群組內
            if (isset($vaildGroup[$table])) {
                // 把該round內的注單都拿出來
                foreach ($roundData as $bets) {
                    $betTypeAry = array();
                    // 把所有單的index 拿出來
                    foreach ($bets as $idx) {
                        $mapBet = $data[$idx];
                        // 如果此單還沒置入陣列, 放進去
                        if(!isset($betTypeAry[$mapBet->content])) {
                            $betTypeAry[$mapBet->content]= array(
                                'betAmount' => $mapBet->betAmount,
                                'idx' => array($idx)
                            );
                        } else {
                            // 如果此單已經在陣列內, betAmount為總投注額
                            $betTypeAry[$mapBet->content]['betAmount'] = $betTypeAry[$mapBet->content]['betAmount'] + $mapBet->betAmount;
                            // 寄存該單有多少個index
                            $betTypeAry[$mapBet->content]['idx'][] = $idx;
                        }
                    }
                    foreach ($vaildGroup[$table] as $group) {
                        // 取得最低投注金額
                        $minBet = $this->getMinBet($betTypeAry, $group);
                        if ($minBet > 0) {
                            // 把需要判斷對壓的群組內容撈出來
                            foreach ($group as $betType) {
                                // 取出需驗證的投注內容
                                $tmpBetItem = $betTypeAry[$betType];
                                // 先拿取最低投注金額
                                $tmpMinBet = $minBet;
                                // 輪巡所有的注單
                                foreach($tmpBetItem['idx'] as $playsubIdx) {
                                    // 如果最低投注金額小於0, 不需要繼續跑了
                                    if ($tmpMinBet <= 0) {
                                        break;
                                    }
                                    // 新的有效投注金額 = 投注 - 最低投注額 (也就是說, 他的有效投注已扣除另一個對壓投注額)
                                    $newVailid = $data[$playsubIdx]->betAmount - $tmpMinBet;
                                    // 暫存的最低投注額 = 最低投注額 - 投注額 (這邊確立, 如果扣成負或0, 就不再進來)
                                    $tmpMinBet = $tmpMinBet - $data[$playsubIdx]->betAmount;
                                    // 如果新的有效投注小於0 以0計算
                                    if ($newVailid < 0) {$newVailid = 0;}
                                    // 更新有效投注額
                                    $data[$playsubIdx]->validAmount = $newVailid;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }
    /**
     * 回傳下注資料分組索引
     * ex:
     * array['table']['username-gameCode-round'] = [0, 2, 5] (在$data內的index)
     *
     * array['BACCARAT']['user123-ogplus_P6-34-48']
     */
    private function getBetGroup($data)
    {
        $betGroup = array();
        for ($idx = 0; $idx < count($data); $idx++) {
            $betItem = $data[$idx];
            $betGroup[$betItem->gameCode][$betItem->username . '-' . $betItem->table][] = $idx;
        }
        return $betGroup;
    }

    // 取得對押群組
    private function getValidGroup()
    {
        $vaildGroup = array();

        //First Person Baccarat
        $vaildGroup["rngbaccarat00000"][]=array('BAC_Banker','BAC_Player'); // 莊家 閒家

        //Baccarat Squeeze
        $vaildGroup["zixzea8nrf1675oh"][]=array('BAC_Banker','BAC_Player'); // 莊家 閒家

        //No Commission Baccarat
        $vaildGroup["NoCommBac0000001"][]=array('BAC_Banker','BAC_Player'); // 莊家 閒家

        //Baccarat Control Sq
        $vaildGroup["k2oswnib7jjaaznw"][]=array('BAC_Banker','BAC_Player'); // 莊家 閒家

        //Lightning Baccarat
        $vaildGroup["LightningBac0001"][]=array('BAC_Banker','BAC_Player'); // 莊家 閒家

        //First Person Dragon Tiger
        $vaildGroup["rng-dragontiger0"][]=array('DT_Dragon','DT_Tiger'); // 龍 虎

        //Super Sic Bo
        $vaildGroup["SuperSicBo000001"][]=array('SicBo_Small','SicBo_Big'); // 小 大
        $vaildGroup["SuperSicBo000001"][]=array('SicBo_Odd','SicBo_Even'); // 單 雙
        $vaildGroup["SuperSicBo000001"][]=array('SicBo_1','SicBo_2','SicBo_3','SicBo_4','SicBo_5','SicBo_6'); // 1 2 3 4 5 6

        // Side Bet City
        $vaildGroup["SBCTable00000001"][]=array('SBC_ThreeCardsBet','SBC_FiveCardsBet','SBC_SevenCardsBet'); // card hand 3     card hand 5   card hand 7

        //First Person Top Car
        $vaildGroup["rng-topcard00001"][]=array('TC_A','TC_B'); // 主場 客場

        // Roulette
        $vaildGroup["vctlz20yfnmp1ylr"][]=array('ROU_DozenBet112','ROU_DozenBet1324','ROU_DozenBet2536');           // 1ST12(數字1~12) 2ND12(數字13~24) 3RD(數字25~36)
        $vaildGroup["vctlz20yfnmp1ylr"][]=array('ROU_ColumnBet36Etc','ROU_ColumnBet14Etc','ROU_ColumnBet25Etc');    // 2TO1(數字3、6、9...36)、 2TO1(數字2、5、8...、35)、 2TO1(數字1、4、7...、34)
        $vaildGroup["vctlz20yfnmp1ylr"][]=array('ROU_118','ROU_1936');              // 小(1~18)，大(19~36)
        $vaildGroup["vctlz20yfnmp1ylr"][]=array('ROU_Odd','ROU_Even');              // 單、雙
        $vaildGroup["vctlz20yfnmp1ylr"][]=array('ROU_Red','ROU_Black');             // 紅、黑
        $vaildGroup["vctlz20yfnmp1ylr"][]=array('ROU_Red','ROU_Black');             // 紅、黑
        $vaildGroup["vctlz20yfnmp1ylr"][]=array('ROU_1Red','ROU_3Red','ROU_5Red','ROU_7Red','ROU_9Red','ROU_12Red','ROU_14Red','ROU_16Red','ROU_18Red','ROU_19Red','ROU_21Red','ROU_23Red','ROU_25Red','ROU_27Red','ROU_34Red','ROU_32Red','ROU_34Red','ROU_36Red','ROU_2Black','ROU_4Black','ROU_6Black','ROU_8Black','ROU_10Black','ROU_11Black','ROU_13Black','ROU_15Black','ROU_17Black','ROU_20Black','ROU_22Black','ROU_24Black','ROU_26Black','ROU_28Black','ROU_29Black','ROU_31Black','ROU_33Black','ROU_35Black');    // 0~36

        // Immersive Roulette
        $vaildGroup["9dxyqtvp0rjqvu6r"][]=array('ROU_DozenBet112','ROU_DozenBet1324','ROU_DozenBet2536');           // 1ST12(數字1~12) 2ND12(數字13~24) 3RD(數字25~36)
        $vaildGroup["9dxyqtvp0rjqvu6r"][]=array('ROU_ColumnBet36Etc','ROU_ColumnBet14Etc','ROU_ColumnBet25Etc');    // 2TO1(數字3、6、9...36)、 2TO1(數字2、5、8...、35)、 2TO1(數字1、4、7...、34)
        $vaildGroup["9dxyqtvp0rjqvu6r"][]=array('ROU_118','ROU_1936');              // 小(1~18)，大(19~36)
        $vaildGroup["9dxyqtvp0rjqvu6r"][]=array('ROU_Odd','ROU_Even');              // 單、雙
        $vaildGroup["9dxyqtvp0rjqvu6r"][]=array('ROU_Red','ROU_Black');             // 紅、黑
        $vaildGroup["9dxyqtvp0rjqvu6r"][]=array('ROU_Red','ROU_Black');             // 紅、黑
        $vaildGroup["9dxyqtvp0rjqvu6r"][]=array('ROU_1Red','ROU_3Red','ROU_5Red','ROU_7Red','ROU_9Red','ROU_12Red','ROU_14Red','ROU_16Red','ROU_18Red','ROU_19Red','ROU_21Red','ROU_23Red','ROU_25Red','ROU_27Red','ROU_34Red','ROU_32Red','ROU_34Red','ROU_36Red','ROU_2Black','ROU_4Black','ROU_6Black','ROU_8Black','ROU_10Black','ROU_11Black','ROU_13Black','ROU_15Black','ROU_17Black','ROU_20Black','ROU_22Black','ROU_24Black','ROU_26Black','ROU_28Black','ROU_29Black','ROU_31Black','ROU_33Black','ROU_35Black');    // 0~36

        // Lightning Roulette
        $vaildGroup["LightningTable01"][]=array('ROU_DozenBet112','ROU_DozenBet1324','ROU_DozenBet2536');           // 1ST12(數字1~12) 2ND12(數字13~24) 3RD(數字25~36)
        $vaildGroup["LightningTable01"][]=array('ROU_ColumnBet36Etc','ROU_ColumnBet14Etc','ROU_ColumnBet25Etc');    // 2TO1(數字3、6、9...36)、 2TO1(數字2、5、8...、35)、 2TO1(數字1、4、7...、34)
        $vaildGroup["LightningTable01"][]=array('ROU_118','ROU_1936');              // 小(1~18)，大(19~36)
        $vaildGroup["LightningTable01"][]=array('ROU_Odd','ROU_Even');              // 單、雙
        $vaildGroup["LightningTable01"][]=array('ROU_Red','ROU_Black');             // 紅、黑
        $vaildGroup["LightningTable01"][]=array('ROU_Red','ROU_Black');             // 紅、黑
        $vaildGroup["LightningTable01"][]=array('ROU_1Red','ROU_3Red','ROU_5Red','ROU_7Red','ROU_9Red','ROU_12Red','ROU_14Red','ROU_16Red','ROU_18Red','ROU_19Red','ROU_21Red','ROU_23Red','ROU_25Red','ROU_27Red','ROU_34Red','ROU_32Red','ROU_34Red','ROU_36Red','ROU_2Black','ROU_4Black','ROU_6Black','ROU_8Black','ROU_10Black','ROU_11Black','ROU_13Black','ROU_15Black','ROU_17Black','ROU_20Black','ROU_22Black','ROU_24Black','ROU_26Black','ROU_28Black','ROU_29Black','ROU_31Black','ROU_33Black','ROU_35Black');    // 0~36

        // First Person Roulette
        $vaildGroup["rng-rt-european0"][]=array('ROU_DozenBet112','ROU_DozenBet1324','ROU_DozenBet2536');           // 1ST12(數字1~12) 2ND12(數字13~24) 3RD(數字25~36)
        $vaildGroup["rng-rt-european0"][]=array('ROU_ColumnBet36Etc','ROU_ColumnBet14Etc','ROU_ColumnBet25Etc');    // 2TO1(數字3、6、9...36)、 2TO1(數字2、5、8...、35)、 2TO1(數字1、4、7...、34)
        $vaildGroup["rng-rt-european0"][]=array('ROU_118','ROU_1936');              // 小(1~18)，大(19~36)
        $vaildGroup["rng-rt-european0"][]=array('ROU_Odd','ROU_Even');              // 單、雙
        $vaildGroup["rng-rt-european0"][]=array('ROU_Red','ROU_Black');             // 紅、黑
        $vaildGroup["rng-rt-european0"][]=array('ROU_Red','ROU_Black');             // 紅、黑
        $vaildGroup["rng-rt-european0"][]=array('ROU_1Red','ROU_3Red','ROU_5Red','ROU_7Red','ROU_9Red','ROU_12Red','ROU_14Red','ROU_16Red','ROU_18Red','ROU_19Red','ROU_21Red','ROU_23Red','ROU_25Red','ROU_27Red','ROU_34Red','ROU_32Red','ROU_34Red','ROU_36Red','ROU_2Black','ROU_4Black','ROU_6Black','ROU_8Black','ROU_10Black','ROU_11Black','ROU_13Black','ROU_15Black','ROU_17Black','ROU_20Black','ROU_22Black','ROU_24Black','ROU_26Black','ROU_28Black','ROU_29Black','ROU_31Black','ROU_33Black','ROU_35Black');    // 0~36

        // Double Ball Roulette
        $vaildGroup["DoubleBallRou001"][]=array('ROU_DozenBet112','ROU_DozenBet1324','ROU_DozenBet2536');           // 1ST12(數字1~12) 2ND12(數字13~24) 3RD(數字25~36)
        $vaildGroup["DoubleBallRou001"][]=array('ROU_ColumnBet36Etc','ROU_ColumnBet14Etc','ROU_ColumnBet25Etc');    // 2TO1(數字3、6、9...36)、 2TO1(數字2、5、8...、35)、 2TO1(數字1、4、7...、34)
        $vaildGroup["DoubleBallRou001"][]=array('ROU_118','ROU_1936');              // 小(1~18)，大(19~36)
        $vaildGroup["DoubleBallRou001"][]=array('ROU_Odd','ROU_Even');              // 單、雙
        $vaildGroup["DoubleBallRou001"][]=array('ROU_Red','ROU_Black');             // 紅、黑
        $vaildGroup["DoubleBallRou001"][]=array('ROU_Red','ROU_Black');             // 紅、黑
        $vaildGroup["DoubleBallRou001"][]=array('ROU_1Red','ROU_3Red','ROU_5Red','ROU_7Red','ROU_9Red','ROU_12Red','ROU_14Red','ROU_16Red','ROU_18Red','ROU_19Red','ROU_21Red','ROU_23Red','ROU_25Red','ROU_27Red','ROU_34Red','ROU_32Red','ROU_34Red','ROU_36Red','ROU_2Black','ROU_4Black','ROU_6Black','ROU_8Black','ROU_10Black','ROU_11Black','ROU_13Black','ROU_15Black','ROU_17Black','ROU_20Black','ROU_22Black','ROU_24Black','ROU_26Black','ROU_28Black','ROU_29Black','ROU_31Black','ROU_33Black','ROU_35Black');    // 0~36

        // American Roulette
        $vaildGroup["AmericanTable001"][]=array('ROU_DozenBet112','ROU_DozenBet1324','ROU_DozenBet2536');           // 1ST12(數字1~12) 2ND12(數字13~24) 3RD(數字25~36)
        $vaildGroup["AmericanTable001"][]=array('ROU_ColumnBet36Etc','ROU_ColumnBet14Etc','ROU_ColumnBet25Etc');    // 2TO1(數字3、6、9...36)、 2TO1(數字2、5、8...、35)、 2TO1(數字1、4、7...、34)
        $vaildGroup["AmericanTable001"][]=array('ROU_118','ROU_1936');              // 小(1~18)，大(19~36)
        $vaildGroup["AmericanTable001"][]=array('ROU_Odd','ROU_Even');              // 單、雙
        $vaildGroup["AmericanTable001"][]=array('ROU_Red','ROU_Black');             // 紅、黑
        $vaildGroup["AmericanTable001"][]=array('ROU_Red','ROU_Black');             // 紅、黑
        $vaildGroup["AmericanTable001"][]=array('ROU_1Red','ROU_3Red','ROU_5Red','ROU_7Red','ROU_9Red','ROU_12Red','ROU_14Red','ROU_16Red','ROU_18Red','ROU_19Red','ROU_21Red','ROU_23Red','ROU_25Red','ROU_27Red','ROU_34Red','ROU_32Red','ROU_34Red','ROU_36Red','ROU_2Black','ROU_4Black','ROU_6Black','ROU_8Black','ROU_10Black','ROU_11Black','ROU_13Black','ROU_15Black','ROU_17Black','ROU_20Black','ROU_22Black','ROU_24Black','ROU_26Black','ROU_28Black','ROU_29Black','ROU_31Black','ROU_33Black','ROU_35Black');    // 0~36
        // VIP Roulette
        $vaildGroup["wzg6kdkad1oe7m5k"][]=array('ROU_DozenBet112','ROU_DozenBet1324','ROU_DozenBet2536');           // 1ST12(數字1~12) 2ND12(數字13~24) 3RD(數字25~36)
        $vaildGroup["wzg6kdkad1oe7m5k"][]=array('ROU_ColumnBet36Etc','ROU_ColumnBet14Etc','ROU_ColumnBet25Etc');    // 2TO1(數字3、6、9...36)、 2TO1(數字2、5、8...、35)、 2TO1(數字1、4、7...、34)
        $vaildGroup["wzg6kdkad1oe7m5k"][]=array('ROU_118','ROU_1936');              // 小(1~18)，大(19~36)
        $vaildGroup["wzg6kdkad1oe7m5k"][]=array('ROU_Odd','ROU_Even');              // 單、雙
        $vaildGroup["wzg6kdkad1oe7m5k"][]=array('ROU_Red','ROU_Black');             // 紅、黑
        $vaildGroup["wzg6kdkad1oe7m5k"][]=array('ROU_Red','ROU_Black');             // 紅、黑
        $vaildGroup["wzg6kdkad1oe7m5k"][]=array('ROU_1Red','ROU_3Red','ROU_5Red','ROU_7Red','ROU_9Red','ROU_12Red','ROU_14Red','ROU_16Red','ROU_18Red','ROU_19Red','ROU_21Red','ROU_23Red','ROU_25Red','ROU_27Red','ROU_34Red','ROU_32Red','ROU_34Red','ROU_36Red','ROU_2Black','ROU_4Black','ROU_6Black','ROU_8Black','ROU_10Black','ROU_11Black','ROU_13Black','ROU_15Black','ROU_17Black','ROU_20Black','ROU_22Black','ROU_24Black','ROU_26Black','ROU_28Black','ROU_29Black','ROU_31Black','ROU_33Black','ROU_35Black');    // 0~36
        return $vaildGroup;
    }
    /**
     * 取得該場下注在指定對押群組玩法的最低下注金額
     * -1 則是沒有對押問題
    */
    private function getMinBet($betAry, $betTypeGroup){
        $min = -1;
        // 把需要判斷對壓的群組內容撈出來
        foreach ($betTypeGroup as $betType) {
            // 如果注單不存在需要判斷對壓的, 回傳-1
            if(!isset($betAry[$betType])) {
                //沒有下注該選項
                return -1;
            }
            // 如果最低下注金額還沒有定義 或 總投注金額小於低下注金額 更換最低投注金額
            if ($min == -1 || $betAry[$betType]['betAmount'] < $min) {
                $min = $betAry[$betType]['betAmount'];
            }
        }
        return $min;
    }

}
