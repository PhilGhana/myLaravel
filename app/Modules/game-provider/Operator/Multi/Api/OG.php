<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Game;
use GameProvider\Operator\BaseApi;

use GameProvider\Operator\Multi\Config\OGConfigConstract;

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

class OG extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $token = null;

    protected $providerId = 0;

    //沒有給回傳錯誤訊息格式, 待確認
    protected $errorMessage = [
        'Forbidden' => 'X-Operator Or X-Key 錯誤',
        'invalid token' => '無效 token',
        'invalid paramater' => '錯誤參數設定',
        'forbidden' => '遊戲商錯誤', //訊息重複, 變成小寫, 狀況是否一樣要再確認
        'InternalServerError' => '內部服務器錯誤',
        'Missing parameter' => '數值不可為空',
        'User not found' => '會員不存在',
        'transderId already exists' => '交易單號已經存在',
        'Transfer failed' => '轉帳失敗',
        'Game not found' => '錯誤的遊戲代號',
        'Access denied' => '錯誤的Operator 或 Key',
        'The s date field is required' => '必填入SDate',
        'The e date field is required' => '必填入EDate',
        'The Minimum should be 10 minutes' => '獲取區間只能在10分鐘',
        'The provider field value is not valid' => '遊戲商填入無效值'
    ];

    function __construct(array $config)
    {
        $this->config = new OGConfigConstract();

        $this->config->apiUrl = $config['apiURL'];
        $this->config->fetchUrl = $config['fetchURL'];
        $this->config->agid = $config['agid'];
        $this->config->secret = $config['secret'];
        $this->config->language = $config['language'];

        $this->providerId = $config['providerId'];
    }

    public function login()
    {
        // 不重複登入
        if ($this->token !== null) {
            return $this->token;
        }

        $tmp = redis()->get('OG_token');
        if($tmp)
        {
            $this->token = $tmp;
            return $tmp;
        }

        // $params = [
        //     'X-Operator:' . $this->config->agid,
        //     'X-key:' . $this->config->secret
        // ];

        // array_push($this->curlHeader, $params);
        $this->curlHeader[] = 'X-Operator:' . $this->config->agid;
        $this->curlHeader[] = 'X-key:' . $this->config->secret;

        $response = $this->get($this->config->apiUrl . '/token', json_encode([]), false);
        $result = json_decode($response);
        if ($result->status === "success") {
            $this->token = $result->data->token;

            // 把token 存起來 因為存活時間的問題
            redis()->set('OG_token', $this->token);
            redis()->expire('OG_token', 350);

            return $this->token;
        }

        throw new LoginException(get_class($this), 'server side login error!');
    }

    public function getGameList()
    {
        $token = $this->login();

        array_push($this->curlHeader, 'X-Token:' . $token);

        $response = $this->get($this->config->apiUrl . '/games?rows=50', json_encode([]));

        // 寫入資料庫
        $data = $response->data->games;
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

        dd($response);
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
        // $lang = $this->setLanguage($member->language);

        array_push($this->curlHeader, 'X-Token:' . $token);

        $params = [
            'username'  => $member->playerId,
            'country'   => 'China',
            'fullname'  => $member->playerId,
            'language'  => $this->config->language,
            'email'     => $member->playerId . '@mm.cc',
            'birthdate' => '1980-02-04',

        ];
        $memberFeedback = new MemberFeedback();

        $url = '/register';
        $result = $this->doSendProcess($params, $url);
        if ($result->status === 'success') {
            return $memberFeedback;
        }

        throw new CreateMemberException(get_class($this), 'create member error! error code : ' . $result->status);
    }

    /**
     * 取得會員餘額
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        $token = $this->login();

        array_push($this->curlHeader, 'X-Token:' . $token);

        // $params = [
        //     'username' => $member->playerId,
        // ];

        // $agid = $this->config->agid;

        $url = $this->config->apiUrl . '/game-providers/' . $this->providerId . '/balance?username=' . $member->playerId;

        $balanceFeedback = new BalanceFeedback();

        $response = $this->get($url, json_encode([]), false);
        $result = json_decode($response);

        if ($result->status === 'success') {
            $balanceFeedback->balance = $result->data->balance;
            return $balanceFeedback;
        }

        throw new BalanceException(get_class($this), 'get balance error! error code : ' . $result->status);
    }

    /**
     * 會員登入（取得遊戲路徑）
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */

    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $token = $this->login();

        array_push($this->curlHeader, 'X-Token:' . $token);

        // $agid = $this->config->agid;
        $gameCode = $launchGameParams->gameId;

        //取得遊戲金鑰
        $keyUrl = $this->config->apiUrl . '/game-providers/' . $this->providerId . '/games/' . $gameCode . '/key?username=' . $launchGameParams->member->playerId;

        // $keyParams = [
        //     'username' => $launchGameParams->member->playerId
        // ];

        $keyResponse = $this->get($keyUrl, json_encode([]), false);
        $keyResult = json_decode($keyResponse);
        $key = '';

        $launchGameFeedback = new LaunchGameFeedback();


        if ($keyResult->status === 'success') {
            $key = $keyResult->data->key;

            $device = ($launchGameParams->device == 'PC')? 'desktop':'mobile';

            $gameUrl = $this->config->apiUrl. '/game-providers/' . $this->providerId . '/play?key=' . $key . '&type=' . $device;


            // $gameParams = [
            //     'key' => $key,
            //     'type' => $device
            // ];

            $gameResponse = $this->get($gameUrl, json_encode([]), false);
            $gameResult = json_decode($gameResponse);
            if ($gameResult->status === 'success') {
                $launchGameFeedback->gameUrl = $gameResult->data->url;
                return $launchGameFeedback;
            }

            throw new LaunchGameException(get_class($this), 'launch game error! error code : ' . $gameResult->status);
        }

        throw new LaunchGameException(get_class($this), 'launch game key error! error code : ' . $keyResult->status);
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
        array_push($this->curlHeader, 'X-Token:' . $token);

        // $agid = $this->config->agid;

        $url = '/game-providers/'.$this->providerId.'/balance';
        $payno = $this->GUID();

        $params = [
            'username'   => $transfer->member->playerId,
            'balance'    => $transfer->amount,
            'action'     => 'IN',
            'transferId' => $payno
        ];
        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($params, $url);

        if($result->status === 'success')
        {
            $transferFeedback->balance = $result->data->balance;
            return $transferFeedback;
        }

        throw new TransferException(get_class($this), 'deposit error! error code : ' . $result->status);
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
        array_push($this->curlHeader, 'X-Token:' . $token);

        $agid = $this->config->agid;

        $url = '/game-providers/'.$this->providerId.'/balance';
        $payno = $this->GUID();

        $params = [
            'username'   => $transfer->member->playerId,
            'balance'    => $transfer->amount,
            'action'     => 'OUT',
            'transferId' => $payno
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($params, $url);

        if($result->status === 'success')
        {
            $transferFeedback->balance = $result->data->balance;

            return $transferFeedback;
        }

        throw new TransferException(get_class($this), 'withdraw error! error code : ' . $result->status);
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
        // $token = $this->login();
        // array_push($this->curlHeader, 'X-Token:' . $token);

        $this->curlHeader[0] = 'Content-Type:application/x-www-form-urlencoded';

        // 單次限拉10分鐘
        $format = 'Y-m-d H:i:s';
        $startAt = Carbon::parse($srp->endAt)->subMinutes(10)->format($format);

        $params = [
            'Operator'          => $this->config->agid,
            'Key'               => $this->config->secret,
            'SDate'             => $startAt,
            'EDate'             => $srp->endAt,
            'Provider'          => 'ogplus',
            // 'PlayerID'          => null,
            // 'TransactionNumber' => null,
            // 'Exact'             => false
        ];
        return $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
        $url = '/transaction';
        $result = $this->doSendProcess($params, $url, true);
        $rows = $result;

        $data = [];

        foreach($rows as $row)
        {
            $data[] = $this->makeSyncCallBackParameter($row);
        }

        //重新計算有效下注
        $data = $this->doVaildCalculate($data);

        return $data;

    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid = $row->bettingcode;
        $callBackParam->username = explode('_', $row->membername)[1];
        $callBackParam->betAmount = $row->bettingamount;
        $callBackParam->validAmount = $row->validbet;
        $callBackParam->gameCode = 'ogplus_' . $row->gameid;
        $callBackParam->winAmount = $row->winloseamount + $row->bettingamount;
        $callBackParam->betAt = $row->bettingdate;
        $callBackParam->status = Report::STATUS_COMPLETED;//格式是:下注區域^下注金額^輸贏金額
        $callBackParam->reportAt = $row->bettingdate; // 結算時間
        $callBackParam->content = $row->bet;
        $callBackParam->table = $row->gamename;
        $callBackParam->round = $row->roundno;

        return $callBackParam;
    }

    private function doSendProcess($params, $apiUrl, $isFetch = false)
    {
        $fullParams = json_encode($params);

        $url = $this->config->apiUrl;
        if($isFetch)
        {
            $url = $this->config->fetchUrl;
            $fullParams = http_build_query($params);
        }

        $response = $this->post($url .$apiUrl, $fullParams, false);

        $result = json_decode($response);

        // 如果解不開，就直接把錯誤丟回去
        if($result === null)
        {
            throw new JSONException(get_class($this), 'error on JSON decode !', $result);
        }

        return $result;
    }

    public function setLanguage($curLang)
    {
        $lang = [
            "en"      => "en",
            "zh-Hans" => "cn",
            "ko"      => "kr",
            "ja"      => "jp",
            "vi"      => "vn",
            "th"      => "th",
            "id"      => "id",
            "in"      => "id",
        ];

        if(array_key_exists($curLang, $lang)){

            return $lang[$curLang];
        }

        return $this->config->language;
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
            $betGroup[$betItem->table][$betItem->username . '-' . $betItem->gameCode . '-' . $betItem->round][] = $idx;
        } 
        return $betGroup;
    }

    // 取得對押群組
    private function getValidGroup()
    {
        $vaildGroup = array();
    	// 极速百家乐
    	$vaildGroup["SPEED BACCARAT"][]=array('player','banker');			// 莊、閒
		
		//百家乐
    	$vaildGroup["BACCARAT"][]=array('player','banker');			// 莊、閒

		//竞咪百家乐
    	$vaildGroup["BIDDING BACCARAT"][]=array('player','banker');			// 莊、閒

		//新式龙虎
    	$vaildGroup["NEW DT"][]=array('dragon','tiger');			// 龍、虎
		
		//經典龙虎
    	$vaildGroup["CLASSIC DT"][]=array('dragon','tiger');			// 龍、虎

		// 輪盤
    	$vaildGroup["ROULETTE"][]=array('dozen1','dozen2','dozen3');			// 行1，行2，行3
    	$vaildGroup["ROULETTE"][]=array('row1','row2','row3');	// 組1，組2，組3
    	$vaildGroup["ROULETTE"][]=array('small','big');				// 小(1~18)，大(19~36)
    	$vaildGroup["ROULETTE"][]=array('odd','even');				// 單、雙
		$vaildGroup["ROULETTE"][]=array('red','black');				// 紅、黑

		// 炸金花
    	$vaildGroup["WIN3CARD"][]=array('3c-phoenix','3c-dragon');			// 鳳、龍
        
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
