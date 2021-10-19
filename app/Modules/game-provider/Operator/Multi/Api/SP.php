<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Report;
use Carbon\Carbon;
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
use GameProvider\Operator\Multi\Config\SPConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
// use MultiWallet\Feedback\SyncCallBackFeedback;

use GameProvider\Operator\Params\TransferParameter;

class SP extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $token = null;

    protected $DisplayName = null;

    protected $errorMessage = [
        '0'   => '成功',
        '100' => '用户名错误',
        '101' => '账户锁定',
        '102' => '密钥错误',
        '104' => '服务器不可用',
        '105' => '客户端错误',
        '106' => '服务器忙碌中, 请稍后再次尝试',
        '107' => '用户名为空',
        '108' => '用户名 长度/格式错误',
        '110' => '用户离线',
        '111' => '查询时间超过限制',
        '112' => '近期已调用',
        '113' => '用户名已存在',
        '114' => '币种不存在',
        '116' => '用户名不存在',
        '120' => '数值必须大于0',
        '121' => '没有足够的信用或借记点及下注',
        '122' => '订单ID已经存在',
        '125' => '踢出用户失败',
        '127' => '订单ID格式错误',
        '128' => '解密错误',
        '129' => '系统正在维护',
        '130' => '用户账户锁定（无效）',
        '132' => '核对不正确',
        '133' => '建立帐户失败',
        '134' => '游戏代码不存在',
        '135' => '游戏没有开放',
        '136' => '没有足够额度投注',
        '137' => '选号字串错误',
        '138' => '投注未开始或已结束',
        '142' => '输入参数错误',
        '144' => '查询类别错误',
        '145' => '输入浮点数超过2位数错误',
        '146' => 'API 调用被禁止',
        '147' => '限紅ID不存在',
        '148' => '最大馀额不等於 0 或小於帐户结馀',
        '149' => '输入数值低於下限',
        '150' => '功能已被废弃',
        '151' => '重复登录',
        '152' => '鱼乐无穷游戏ID不存在',
        '153' => '投注编号不存在',
    ];

    public function __construct(array $config)
    {
        $this->config = new SPConfigConstract();

        $this->config->apiUrl       = $config['apiUrl'];
        // $this->config->secret       = $config['secret'];
        $this->config->CurrencyType = $config['CurrencyType'];
        $this->config->secret       = $config['secret'];
        $this->config->EncrypKey    = $config['EncrypKey'];
        $this->config->lang         = $config['lang'];
        $this->config->MD5Key       = $config['MD5Key'];
        // $this->config->username     = $config['username'];
        $this->config->lobbycode    = $config['lobbycode'];
        // $this->config->gameUrl      = $config['gameUrl'];

        $this->doStuck = false;
    }

    /**
     * 獲取遊戲列表
     *
     * @return void
     */
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
        // $token = $this->login();
        $method = 'RegUserInfo';

        $params = [
            'method'       => $method,
            'key'          => $this->config->secret,
            'Username'     => $member->playerId,
            'CurrencyType' => $this->config->CurrencyType,
        ];

        $memberFeedback = new MemberFeedback();

        $result = $this->doSendProcess($params);

        if ($result->ErrorMsgId === '0') {
            $memberFeedback->extendParam = $result->Username;

            return $memberFeedback;
        }

        throw new CreateMemberException(get_class($this), 'create member error! error code : ' . $result->ErrorMsgId . '  ' . $this->errorMessage[$result->ErrorMsgId]);
        // $memberFeedback->error_code = $result->respcode;
        // $memberFeedback->error_msg = $this->errorMessage[$result->respcode];

        // return $memberFeedback;
    }

    /**
     * 存款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        // $token  = $this->login();
        $method = 'CreditBalance';

        // 注意這個遊戲有提到他們的訂單編號有獨立格式
        $now     = Carbon::now()->format('YmdHis');
        $orderId = 'IN' . $now . $transfer->member->playerId;

        $params = [
            'method'       => $method,
            'key'          => $this->config->secret,
            'Username'     => $transfer->member->playerId,
            'OrderId'      => $orderId,
            'CreditAmount' => $transfer->amount,
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($params);

        // 如果正確
        if ($result->ErrorMsgId === '0') {
            $transferFeedback->balance      = $result->Balance;
            $transferFeedback->remote_payno = $result->OrderId;
            // $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }

        // 發生錯誤
        throw new TransferException(
            get_class($this),
            'deposit error! error code : ' . $result->ErrorMsgId,
            $this->errorMessage[$result->ErrorMsgId] ?? ('Unknow:' . $result->ErrorMsgId),
            $this->doStuck
        );
        // $transferFeedback->error_code = $result->respcode;
        // $transferFeedback->error_msg = $this->errorMessage[$result->respcode];

        // return $transferFeedback;
    }

    /**
     * 提款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        // $token  = $this->login();
        $method = 'DebitBalance';

        // 注意這個遊戲有提到他們的訂單編號有獨立格式
        $now     = Carbon::now()->format('YmdHis');
        $orderId = 'OUT' . $now . $transfer->member->playerId;

        $params = [
            'method'      => $method,
            'key'         => $this->config->secret,
            'Username'    => $transfer->member->playerId,
            'OrderId'     => $orderId,
            'DebitAmount' => $transfer->amount,
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($params);

        if ($result->ErrorMsgId === '0') {
            $transferFeedback->balance      = $result->Balance;
            $transferFeedback->remote_payno = $result->OrderId;
            // $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }
        // 發生錯誤
        throw new TransferException(
            get_class($this),
            'withdraw error! error code : ' . $result->ErrorMsgId,
            $this->errorMessage[$result->ErrorMsgId] ?? ('Unknow:' . $result->ErrorMsgId),
            $this->doStuck
        );
        // $transferFeedback->error_code = $result->respcode;
        // $transferFeedback->error_msg = $this->errorMessage[$result->respcode];

        // return $transferFeedback;
    }

    /**
     * 會員登入（取得遊戲路徑）
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {

        // 為什麼要多執行一次
        // $token = $this->login();

        $method = 'LoginRequest';

        $params = [
            'method'       => $method,
            'key'          => $this->config->secret,
            'Username'     => $launchGameParams->member->playerId,
            'CurrencyType' => $this->config->CurrencyType,
            'GameCode'     => $launchGameParams->gameId,
            'Lang'         => $this->config->lang,
            'Mobile'       => ($launchGameParams->device == 'mobile') ? 1 : 0,
            // 'token'        => $token,
            // 'name'         => $this->DisplayName,
            // 'mobile'       => false,
            // 'lobbycode'    => $this->config->lobbycode,
        ];

        $launchGameFeedback = new LaunchGameFeedback();

        $result = $this->doSendProcess($params);

        if ($result->ErrorMsgId === '0') {
            $launchGameFeedback->gameUrl       = $result->GameURL;
            $launchGameFeedback->mobileGameUrl = $result->GameURL;
            $launchGameFeedback->token         = $result->Token;
            // $launchGameFeedback->response_code = $this->reponseCode;

            return $launchGameFeedback;
        }

        // 發生錯誤
        throw new LaunchGameException(get_class($this), 'launch game error! error code : ' . $result->ErrorMsgId . ' ' . $this->errorMessage[$result->ErrorMsgId]);
        // $launchGameFeedback->error_code = $result->respcode;
        // $launchGameFeedback->error_msg = $this->errorMessage[$result->respcode];

        // return $launchGameFeedback;
    }

    /**
     * 取得會員餘額
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        // $token = $this->login();

        $method = 'GetUserStatus';

        $params = [
            'method'   => $method,
            'key'      => $this->config->secret,
            'Username' => $member->playerId,
        ];
        $balanceFeedback = new BalanceFeedback();

        $result = $this->doSendProcess($params);

        if ($result->ErrorMsgId === '0') {
            // $balanceFeedback->response_code = $this->reponseCode;
            $balanceFeedback->balance       = $result->Balance;

            return $balanceFeedback;
        }

        // 發生錯誤
        throw new BalanceException(get_class($this), 'get balance error! error code : ' . $result->ErrorMsgId . ' ' . $this->errorMessage[$result->ErrorMsgId]);
    }

    /**
     * 同步注單(取回時間區段的所有注單)
     *
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $method = 'GetAllBetDetailsForTimeInterval';

        $params = [
            'method'   => $method,
            'key'      => $this->config->secret,
            'FromTime' => $srp->startAt,
            'ToTime'   => $srp->endAt,
        ];

        return $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
        // $callBackFeedback = new SyncCallBackFeedback();

        $result = $this->doSendProcess($params);

        if ($result->ErrorMsgId === '0') {
            $rows = $result->BetDetailList;

            $data = [];

            if (! $rows) {
                return $data;
            }

            // sp 特例 : 如果只有一個物件, 就會給object 要小心
            if (is_object($rows->BetDetail)) {
                $data[] = $this->makeSyncCallBackParameter($rows->BetDetail);
            } else {
                foreach ($rows->BetDetail as $row) {
                    $data[] = $this->makeSyncCallBackParameter($row);
                }
            }

            // if ($result->totalpage > $params['page']) {
            //     $params['page'] = $params['page'] + 1;
            //     $data           = array_merge($data, $this->doSyncReport($params));
            // }

            return $data;
        }

        throw new SyncException(get_class($this), 'sync error! error code : ' . $result->ErrorMsgId, $this->errorMessage[$result->ErrorMsgId]);
        // $callBackFeedback->error_code = $result->respcode;
        // $callBackFeedback->error_msg = $this->errorMessage[$result->respcode];

        // return $callBackFeedback;
    }

    private function makeSyncCallBackParameter($row)
    {
        $format = 'Y-m-d H:i:s';
        $now    = date($format);

        $callBackParam = new SyncCallBackParameter();

        // report at 一定要有不然報表會不顯示
        $callBackParam->betAt       = $row->BetTime;
        $callBackParam->reportAt    = $row->BetTime;
        $callBackParam->settleAt    = $row->PayoutTime;
        $callBackParam->username    = $row->Username;

        $gameCode = '';
        if ($row->GameID == 'EG-FISHING-001') {
            $gameCode = 'EG-FISHING-001';
        } else {
            $gameCode = $row->Detail;
        }
        $callBackParam->gameCode    = $gameCode;
        $callBackParam->mid         = $row->BetID;
        $callBackParam->betAmount   = $row->BetAmount;
        $callBackParam->validAmount = $row->Rolling;
        // SP的中獎金額不含本金, 所以要加回來
        $callBackParam->winAmount   = $row->ResultAmount + $row->BetAmount;
        $callBackParam->content     = $row->BetSource;

        // 沒給status 要補上阿
        $callBackParam->status = Report::STATUS_COMPLETED;

        return $callBackParam;
    }

    public function login()
    {
        // 如果登過了，不要重複登
        if ($this->token !== null) {
            return $this->token;
        }

        $method = 'LoginRequest';

        $params = [
            'method'       => $method,
            'key'          => $this->config->secret,
            'Username'     => $this->config->username,
            'CurrencyType' => $this->config->CurrencyType,
        ];

        $result = $this->doSendProcess($params);

        if ($result->ErrorMsgId === '0') {
            $this->token       = $result->Token;
            $this->DisplayName = $result->DisplayName;
            return $this->token;
        }

        throw new LoginException(get_class($this), 'server side login error!');
    }

    private function doSendProcess($params)
    {
        $now    = Carbon::now()->format('YmdHis');

        // 如果時間和md5的不統一, 遊戲方肯定會解不開
        $params['Time'] = $now;

        $fullParams = $this->setParams($params);

        $response = $this->SPpost($this->config->apiUrl, $fullParams, false);

        $result = $this->xml2js($response);

        // 如果解不開，就直接把錯誤丟回去
        if ($result === null) {
            throw new JSONException(get_class($this), 'error on JSON decode !', $result);
        }

        return $result;
    }

    private function setParams($params)
    {
        $str        = http_build_query($params);
        $desStr     = $this->encrypt($str);
        $md5Str     = $this->md5Process($str, $params['Time']);
        $fullParams = [
            'q' => $desStr,
            's' => $md5Str,
        ];

        return http_build_query($fullParams);
    }

    public function encrypt($data)
    {
        // dd($data);
        $result = openssl_encrypt($data, 'DES-CBC', $this->config->EncrypKey, OPENSSL_RAW_DATA, $this->config->EncrypKey);

        if ($result === false) {
            throw new AesException(get_class($this), 'error on DES encrypt !', json_encode($data));
        }

        return base64_encode($result);
    }

    public function md5Process($data, $time)
    {
        // $time   = Carbon::now()->format('YmdHis');
        $result = md5($data . $this->config->MD5Key . $time . $this->config->secret);
        return $result;
    }

    // public static function decrypt($data, $key)
    // {
    //     $encrypted = base64_decode($data);

    //     $decode = openssl_decrypt($encrypted, 'DES-CBC', $key, OPENSSL_RAW_DATA);

    //     if ($decode === false) {
    //         throw new AesException(get_class($this), 'error on DES decode !', $data);
    //     }

    //     return $decode;
    // }

    protected function SPcurl($content, $url, $isPost = true, $need_json = true, $need_array = false)
    {
        $header = ['Content-Type: application/x-www-form-urlencoded'];
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        if ($isPost === true) {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        $result = curl_exec($ch);

        $reponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // 如果對方發生錯誤，直接報錯，不處理
        if ($reponseCode !== 200) {
            // TODO : 這邊要寫到log
            throw new CurlException(get_class($this), 'curl error : ' . $url, json_encode($content));
        }

        curl_close($ch);

        if ($need_json === true) {
            return json_decode($result, $need_array);
        }

        return $result;
    }

    protected function SPpost($url, $content, $need_json = true, $need_array = false)
    {
        return $this->SPcurl($content, $url, true, $need_json, $need_array);
    }

    // xml 解析
    public function xml2js($xmlnode)
    {
        $xml  = simplexml_load_string($xmlnode);
        $xml  = json_encode($xml);
        $xml  = json_decode($xml, true);
        $json = json_encode($xml);
        $json = json_decode($json);

        return $json;
    }
}
