<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Report;
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
use GameProvider\Operator\Multi\Config\DGConfigConstruct;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
// use MultiWallet\Feedback\SyncCallBackFeedback;

use GameProvider\Operator\Params\TransferParameter;

class DG extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $token = null;

    protected $errorMessage = [
        '0'   => '操作成功',
        '1'   => '参数错误',
        '2'   => 'Token验证失败',
        '4'   => '非法操作',
        '10	' => '日期格式错误',
        '11	' => '数据格式错误',
        '97	' => '没有权限',
        '98	' => '操作失败',
        '99	' => '未知错误',
        '100' => '账号被锁定',
        '101' => '账号格式错误',
        '102' => '账号不存在',
        '103' => '此账号被占用',
        '104' => '密码格式错误',
        '105' => '密码错误',
        '106' => '新旧密码相同',
        '107' => '会员账号不可用',
        '108' => '登入失败',
        '109' => '注册失败',
        '113' => '传入的代理账号不是代理',
        '114' => '找不到会员',
        '116' => '账号已占用',
        '117' => '找不到会员所属的分公司',
        '118' => '找不到指定的代理',
        '119' => '存取款操作时代理点数不足',
        '120' => '余额不足',
        '121' => '盈利限制必须大于或等于0',
        '150' => '免费试玩账号用完',
        '300' => '系统维护',
        '301' => '代理账号找不到',
        '320' => 'API Key 错误',
        '321' => '找不到相应的限红组',
        '322' => '找不到指定的货币类型',
        '323' => '转账流水号占用',
        '324' => '转账失败',
        '325' => '代理状态不可用',
        '326' => '会员代理没有视频组',
        '328' => 'API 类型找不到',
        '329' => '会员代理信息不完整',
        '400' => '客户端IP 受限',
        '401' => '网络延迟',
        '402' => '连接关闭',
        '403' => '客户端来源受限',
        '404' => '请求的资源不存在',
        '405' => '请求太频繁',
        '406' => '请求超时',
        '407' => '找不到游戏地址',
        '500' => '空指针异常',
        '501' => '系统异常',
        '502' => '系统忙',
        '503' => '数据操作异常',
    ];

    public function __construct(array $config)
    {
        $this->config = new DGConfigConstruct();

        $this->config->apiUrl    = $config['apiUrl'];
        $this->config->agentName = $config['agentName'];
        $this->config->APIkey    = $config['APIkey'];
        // 隨機產生字串代表要你真的隨機產生一組, 不是要再設定弄
        // $this->config->random    = $config['random'];
        $this->config->random    = $this->getRandomBytes();
        $this->config->data      = $config['data'];
        $this->config->currency  = $config['currency'];
        $this->config->winLimit  = $config['winLimit'];
        $this->config->lang      = $config['lang'];

        $this->config->token = md5($this->config->agentName.$this->config->APIkey.$this->config->random);
    }

    private function getRandomBytes($length = 16)
    {
        if (function_exists('random_bytes')) {
            $bytes = random_bytes($length / 2);
        } else {
            $bytes = openssl_random_pseudo_bytes($length / 2);
        }

        return bin2hex($bytes);
    }

    /**
     * 獲取遊戲列表.
     *
     * @return void
     */
    public function getGameList()
    {
    }

    public function updateLimit(MemberParameter $member)
    {
        $apiUrl = $this->config->apiUrl.'/game/updateLimit/'.$this->config->agentName;

        $params = [
            'token'  => $this->config->token,
            'random' => $this->config->random,
            'data'   => $this->config->data,
            'member' => [
                'username' => $member->playerId,
            ],
        ];

        $memberFeedback = new MemberFeedback();

        $result = $this->doSendProcess($memberFeedback, $apiUrl, $params);

        if ($result->codeId != 0) {
            $msg = isset($this->errorMessage[$result->codeId]) ? $this->errorMessage[$result->codeId] : '';
            throw new CreateMemberException(get_class($this), 'updateLimit error! error code : '.$result->codeId.'  '.$msg);
        }
    }

    /**
     * 建立會員
     *
     * @return void
     */
    public function createMember(MemberParameter $member)
    {
        $apiUrl = $this->config->apiUrl.'/user/signup/'.$this->config->agentName;

        // 文件不是有交代密碼要先md5嗎?
        $params = [
            'token'  => $this->config->token,
            'random' => $this->config->random,
            'data'   => $this->config->data,
            'member' => [
                'username'     => $member->playerId,
                'password'     => md5($member->password),
                'currencyName' => $this->config->currency,
                'winLimit'     => $this->config->winLimit,
            ],
        ];

        $memberFeedback = new MemberFeedback();

        $result = $this->doSendProcess($memberFeedback, $apiUrl, $params);

        // 出錯就確實要丟錯出來, 不可以只丟有設定錯誤訊息的, 不然怎麼出事的都不知道, 以下同類錯誤我就不再標了
        if ($result->codeId != 0) {
            $msg = isset($this->errorMessage[$result->codeId]) ? $this->errorMessage[$result->codeId] : '';
            throw new CreateMemberException(get_class($this), 'create member error! error code : '.$result->codeId.'  '.$msg);
        }
        // if (!empty($this->errorMessage[$result->codeId]) && $result->codeId != 0) {
        //     throw new CreateMemberException(get_class($this), 'create member error! error code : ' . $result->codeId, $this->errorMessage[$result->codeId]);
        // }

        return $memberFeedback;
    }

    /**
     * 存款.
     *
     * @return void
     */
    public function deposit(TransferParameter $transfer)
    {
        // 連網址都可以錯
        // $apiUrl = $this->config->apiUrl . "/user/transfer/" . $this->config->agentName;
        $apiUrl = $this->config->apiUrl.'/account/transfer/'.$this->config->agentName;

        // 這裡面的$member 哪裡來的= =
        $params = [
            'token'  => $this->config->token,
            'random' => $this->config->random,
            'data'   => $transfer->member->playerId.time(),
            'member' => [
                // "username" => $member->username,
                'username' => $transfer->member->playerId,
                'amount'   => $transfer->amount,
            ],
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($transferFeedback, $apiUrl, $params);

        if ($result->codeId != 0) {
            $msg = isset($this->errorMessage[$result->codeId]) ? $this->errorMessage[$result->codeId] : $result->codeId;
            throw new TransferException(get_class($this), $result->codeId.'  '.$msg);
        }

        // 流水號要接回來啊!
        $transferFeedback->remote_payno = $result->data;
        $transferFeedback->balance      = $result->member->balance;

        return $transferFeedback;
    }

    /**
     * 提款.
     *
     * @return void
     */
    public function withdraw(TransferParameter $transfer)
    {
        $apiUrl = $this->config->apiUrl.'/account/transfer/'.$this->config->agentName;

        $params = [
            'token'  => $this->config->token,
            'random' => $this->config->random,
            'data'   => $transfer->member->playerId.time(),
            'member' => [
                'username' => $transfer->member->playerId,
                'amount'   => $transfer->amount * -1,
            ],
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($transferFeedback, $apiUrl, $params);

        if ($result->codeId != 0) {
            $msg = isset($this->errorMessage[$result->codeId]) ? $this->errorMessage[$result->codeId] : $result->codeId;
            throw new TransferException(get_class($this), $result->codeId.'  '.$msg);
        }

        $transferFeedback->remote_payno = $result->data;
        $transferFeedback->balance      = $result->member->balance;

        return $transferFeedback;
    }

    /**
     * 會員登入（取得遊戲路徑）.
     *
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $apiUrl = $this->config->apiUrl.'/user/login/'.$this->config->agentName;

        // 文件都告訴你domains是1了
        $params = [
            'token'   => $this->config->token,
            'random'  => $this->config->random,
            'lang'    => $this->config->lang,
            'domains' => '1',
            'member'  => [
                'username' => $launchGameParams->member->playerId,
                'password' => $launchGameParams->member->password,
            ],
        ];
        // 文件有說這件事嗎?
        // if ($launchGameParams->device == 'mobile') {
        //     $params["domains"] = 1;
        // }

        $launchGameFeedback = new LaunchGameFeedback();

        $result = $this->doSendProcess($launchGameFeedback, $apiUrl, $params);

        if ($result->codeId != 0) {
            $msg = isset($this->errorMessage[$result->codeId]) ? $this->errorMessage[$result->codeId] : '';
            throw new LaunchGameException(get_class($this), 'launchGame error! error code : '.$result->codeId.'  '.$msg);
        }

        // 文件是這樣寫的嗎?
        $launchGameFeedback->gameUrl       = $result->list[0].$result->token.'&language='.$this->config->lang;
        $launchGameFeedback->mobileGameUrl = $result->list[1].$result->token.'&language='.$this->config->lang;
        // $launchGameFeedback->gameUrl       = $result->list[0];
        // $launchGameFeedback->mobileGameUrl = $result->list[1];

        return $launchGameFeedback;
    }

    /**
     * 取得會員餘額.
     *
     * @return void
     */
    public function getBalance(MemberParameter $member)
    {
        $apiUrl = $this->config->apiUrl.'/user/getBalance/'.$this->config->agentName;

        $params = [
            'token'  => $this->config->token,
            'random' => $this->config->random,
            'member' => [
                'username' => $member->playerId,
            ],
        ];

        // 亂寫一通耶
        $balanceFeedback = new BalanceFeedback();
        // $memberFeedback = new MemberFeedback();

        $result = $this->doSendProcess($balanceFeedback, $apiUrl, $params);

        if ($result->codeId != 0) {
            $msg = isset($this->errorMessage[$result->codeId]) ? $this->errorMessage[$result->codeId] : '';
            throw new BalanceException(get_class($this), 'get balance error! error code : '.$result->codeId.'  '.$msg);
        }

        // 餘額不用回傳?
        $balanceFeedback->balance = $result->member->balance;

        return $balanceFeedback;
    }

    /**
     * 同步注單 (取得遊戲每日統計資訊(全部遊戲類型)).
     *
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $apiUrl = $this->config->apiUrl.'/game/getReport/'.$this->config->agentName;

        $params = [
            'token'  => $this->config->token,
            'random' => $this->config->random,
        ];

        $result = $this->doSendProcess(null, $apiUrl, $params);

        if ($result->codeId != 0) {
            $msg = isset($this->errorMessage[$result->codeId]) ? $this->errorMessage[$result->codeId] : '';
            throw new SyncException(get_class($this), 'syncReport error! error code : '.$result->codeId.'  '.$msg);
        }

        // 我真的不懂你是參考哪裡寫出來的...
        // return $callback($result);
        $list = [];
        if (isset($result->list)) {
            $list = $result->list;
        }

        $data = [];
        foreach ($list as $row) {
            $data[] = $this->makeSyncCallBackParameter($row);
        }

        return $callback($data);
    }

    public function callMarkReport($mids)
    {
        // 沒有完成的住單就不要勉強送了
        if (count($mids) == 0) {
            return;
        }

        $apiUrl = $this->config->apiUrl.'/game/markReport/'.$this->config->agentName;

        $params = [
            'token'  => $this->config->token,
            'random' => $this->config->random,
            'list'   => $mids,
        ];

        $result = $this->doSendProcess(null, $apiUrl, $params);

        if ($result->codeId != 0) {
            $msg = isset($this->errorMessage[$result->codeId]) ? $this->errorMessage[$result->codeId] : '';
            throw new SyncException(get_class($this), 'callMarkReport error! error code : '.$result->codeId.'  '.$msg);
        }
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam           = new SyncCallBackParameter();
        $callBackParam->mid      = $row->id; // 注單ID
        $callBackParam->gameCode = $this->getGameCode($row->gameType, (string) $row->tableId, $row->gameId);
        $callBackParam->username = strtolower($row->userName); // 下注會員帳號
        $callBackParam->betAt    = $row->betTime; // 下注時間
        $callBackParam->reportAt = $row->betTime; // 結算時間 這邊如果不用下注時間 當拿到NULL時, 報表會不出現
        $callBackParam->table    = $row->playId; // 這邊吃局號
        $callBackParam->round    = $row->shoeId; // 這邊吃靴號
        // $callBackParam->waterAmount = ;
        $callBackParam->betAmount   = $row->betPoints; // 下注時間金額
        $callBackParam->validAmount = $row->availableBet ? $row->availableBet : 0; // 有效下注
        $callBackParam->winAmount   = $row->winOrLoss ? $row->winOrLoss : 0; // 輸贏金額
        // $callBackParam->prize =  $row->winOrLoss;
        // $callBackParam->tip =  $row->winOrLoss;
        $callBackParam->ip      = $row->ip; //下注IP
        $callBackParam->content = [
            'GameType'   => $row->gameType,             // 游戏类型
            'GameId'     => $row->gameId,               // 游戏Id
            'TableId'    => $row->tableId ?? '',        // 桌號
            'result'     => $row->result ?? null,       // 游戏结果
            'betDetail'  => $row->betDetail ?? null,    // 下注注单
            'deviceType' => $row->deviceType ?? null,   // 下注时客户端类型
        ];

        $status = [
            '0'  => Report::STATUS_BETTING,
            '1'  => Report::STATUS_COMPLETED,
            '2'  => Report::STATUS_CANCEL,
        ];

        $callBackParam->status = $status[(string) $row->isRevocation];

        return $callBackParam;
    }

    private function getGameCode(int $gametype, string $tableId, int $gameId)
    {
        if ($gametype == 2) {
            // 這邊是特殊支出
            $typeAry = [
                1 => 'DG_envelope_send',
                2 => 'DG_envelope_get',
                3 => 'DG_tip',
                4 => 'DG_envelope_company',
                5 => 'DG_bet_cookie',
            ];

            return $typeAry[$gameId];
        }

        // 一般遊戲
        $typeAry = [
            '10101' => 'DG01',
            '10102' => 'DG02',
            '10103' => 'DG03',
            '10105' => 'DG05',
            '10106' => 'DG06',
            '10107' => 'DG07',

            '10301' => 'DG12',
            '10401' => 'DG13',
            '10501' => 'DG15',
            '10701' => 'DG16',
            '11101' => 'DG17',
            '11201' => 'DG18',

            '30101' => 'CT01',
            '30102' => 'CT02',
            '30103' => 'CT03',
            '30105' => 'CT05',
            '30301' => 'CT06',
            '30401' => 'CT08',
            '30601' => 'CT10',

            '40101' => 'CT21',
            '40102' => 'CT22',
            '40103' => 'CT28',
            '40501' => 'CT27',

            '20801' => 'DG08',
            '20802' => 'DG09',
            '20803' => 'DG10',
            '20805' => 'DG11',

            '50101' => 'E1',
            '50102' => 'E3',
            '50103' => 'E7',
            '50401' => 'R1',

            '70101' => 'GC01',
            '70102' => 'GC02',
            '70103' => 'GC03',
            '70105' => 'GC05',
            '70106' => 'GC06',
            '70301' => 'GC07',
            '70401' => 'GC08',
            '70501' => 'GC09',
            '70701' => 'GC10',
            '71401' => 'GC11',
            '71501' => 'GC12',

            '65101' => 'CP01',
            '65201' => 'CP02',
        ];

        return $typeAry[$tableId];
    }

    private function doSendProcess($feedback, $apiUrl, $params)
    {
        $fullParams = json_encode($params);

        $response = $this->post($apiUrl, $fullParams, false);

        return json_decode($response);
    }
}
