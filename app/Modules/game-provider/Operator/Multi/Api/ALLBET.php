<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Report;
use Carbon\Carbon;
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
use GameProvider\Operator\Multi\Config\ALLBETConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
// use MultiWallet\Feedback\SyncCallBackFeedback;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;

class ALLBET extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $token = null;

    protected $errorMessage = [
        'OK'                          => '成功',
        'INTERNAL_ERROR'              => '服务端错误',
        'ILLEGAL_ARGUMENT'            => '参数错误',
        'SYSTEM_MATAINING'            => '系统维护状态',
        'AGENT_NOT_EXIST'             => '代理商不存在',
        'CLIENT_EXIST'                => '玩家已存在',
        'CLIENT_PASSWORD_INCORRECT'   => '密码错误',
        'TOO_FREQUENT_REQUEST'        => '请求过于频繁',
        'CLIENT_NOT_EXIST'            => '玩家不存在',
        'TRANS_EXISTED'               => '转账记录已存在',
        'LACK_OF_MONEY'               => '额度转出的代理商或者玩家额度不足',
        'DUPLICATE_CONFIRM'           => '重复确认转账',
        'TRANS_NOT_EXIST'             => '转账记录不存在',
        'DECRYPTION_FAILURE'          => '解密失败',
        'FORBIDDEN'                   => '禁止操作, 请求IP未在白名单中',
        'INCONSISTENT_WITH_PRE_TRANS' => '确认转账信息与预转账提交信息不一致',
        'INVALID_PROPERTYID'          => '无效的PropertyId',
        'INVALID_SIGN'                => '无效签名',
        'TRANS_FAILURE'               => '转账失败',
    ];

    public function __construct(array $config)
    {
        $this->config = new ALLBETConfigConstract();

        $this->config->apiUrl = $config['apiUrl'];
        // $this->config->client             = $config['client'];
        // $this->config->password           = $config['password'];
        $this->config->agent              = $config['agent'];
        $this->config->ALLBET_DES_KEY     = $config['ALLBET_DES_KEY'];
        $this->config->ALLBET_MD5_KEY     = $config['ALLBET_MD5_KEY'];
        $this->config->ALLBET_PROPERTY_ID = $config['ALLBET_PROPERTY_ID'];
        $this->config->ID_CODE            = $config['ID_CODE'] ?? '';

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
        $url = $this->config->apiUrl . '/check_or_create';
        // 使用安全隨機數,這裡待確認(數字)
        $random = mt_rand(10000, 99999);
        // 大于等于0,小于直属上级代理商的设置的该值
        $orHallRebate = 0;

        $params = [
            'random'       => $random,
            'agent'        => $this->config->agent,
            'client'       => $member->playerId,
            'password'     => $member->password,
            'orHallRebate' => $orHallRebate,
        ];

        $memberFeedback = new MemberFeedback();

        $result = $this->doSendProcess($params, $url);

        if ($result->error_code === 'OK') {
            $memberFeedback->extendParam = $member->playerId . $this->config->ID_CODE;  // APP登入需要
            return $memberFeedback;
        }

        throw new CreateMemberException(get_class($this), 'create member error! error code : ' . $result->error_code, $this->errorMessage[$result->error_code]);
    }

    /**
     * 存款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $url = $this->config->apiUrl . '/agent_client_transfer';
        // 使用安全隨機數,這裡待確認(數字)
        $random = mt_rand(10000, 99999);

        // 聽說文件有規定SN喔
        $params = [
            'random'   => $random,
            'agent'    => $this->config->agent,
            'sn'       => $this->makeOrderId(),
            'client'   => $transfer->member->playerId,
            'operFlag' => 1,
            'credit'   => $transfer->amount,
        ];

        $transferFeedback = new TransferFeedback();
        $transferFeedback->remote_payno = $params['sn'];

        $result = $this->doSendProcess($params, $url);

        // 如果正確
        if ($result->error_code === 'OK') {
            return $transferFeedback;
        }

        // 發生錯誤
        throw new TransferException(
            get_class($this),
            'deposit error! error code : ' . $result->error_code,
            $this->errorMessage[$result->error_code] ?? ('Unknow:' . $result->error_code),
            $this->doStuck
        );
    }

    /**
     * 提款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {

        $url = $this->config->apiUrl . '/agent_client_transfer';
        // 使用安全隨機數,這裡待確認(數字)
        $random = mt_rand(10000, 99999);

        $params = [
            'random'   => $random,
            'agent'    => $this->config->agent,
            'sn'       => $this->makeOrderId(),
            'client'   => $transfer->member->playerId,
            'operFlag' => 0,
            'credit'   => $transfer->amount,
        ];

        $transferFeedback = new TransferFeedback();
        $transferFeedback->remote_payno = $params['sn'];

        $result = $this->doSendProcess($params, $url);

        // 如果正確
        if ($result->error_code === 'OK') {
            return $transferFeedback;
        }

        // 發生錯誤
        throw new TransferException(
            get_class($this),
            'withdraw error! error code : ' . $result->error_code,
            $this->errorMessage[$result->error_code] ?? ('Unknow:' . $result->error_code),
            $this->doStuck
        );
    }

    private function makeOrderId()
    {
        $orderId = $this->config->ALLBET_PROPERTY_ID . time();
        $len     = strlen($orderId);

        if ($len < 20) {
            $num = 20 - $len; // 須補多少字
            $min = 0;
            if ($num == 1) {
                $min = 0;
            } else {
                $min = pow(10, $num - 1);
            }

            $max = 0;
            if ($min == 0) {
                $max = 9;
            } else {
                $max = ($min * 10) - 1;
            }

            $orderId = $orderId . mt_rand($min, $max);
        }

        // var_dump($orderId);

        return $orderId;
    }

    /**
     * 會員登入（取得遊戲路徑）
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {

        $url = $this->config->apiUrl . '/forward_game';
        // 使用安全隨機數,這裡待確認(數字)
        $random = mt_rand(10000, 99999);

        $params = [
            'random'   => $random,
            'client'   => $launchGameParams->member->playerId,
            'password' => $launchGameParams->member->password,
        ];

        $launchGameFeedback = new LaunchGameFeedback();

        $result = $this->doSendProcess($params, $url);

        if ($result->error_code === 'OK') {
            $launchGameFeedback->gameUrl       = $result->gameLoginUrl;
            $launchGameFeedback->mobileGameUrl = $result->gameLoginUrl;

            return $launchGameFeedback;
        }

        // 發生錯誤
        throw new LaunchGameException(get_class($this), 'launch game error! error code : ' . $result->error_code, $this->errorMessage[$result->error_code]);
    }

    /**
     * 取得會員餘額.
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {

        $url = $this->config->apiUrl . '/get_balance';
        // 使用安全隨機數,這裡待確認(數字)
        $random = mt_rand(10000, 99999);

        $params = [
            'random'   => $random,
            'client'   => $member->playerId,
            'password' => $member->password,
        ];

        $balanceFeedback = new BalanceFeedback();

        $result = $this->doSendProcess($params, $url);

        if ($result->error_code === 'OK') {
            $balanceFeedback->balance = $result->balance;

            return $balanceFeedback;
        }

        // 發生錯誤
        throw new BalanceException(get_class($this), 'get balance error! error code : ' . $result->error_code, $this->errorMessage[$result->error_code]);
    }

    /**
     * 同步注單(取回時間區段的所有注單).
     *
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $url = $this->config->apiUrl.'/betlog_pieceof_histories_in30days';

        // 使用安全隨機數,這裡待確認(數字)
        $random = mt_rand(10000, 99999);

        // 最大時間區間必須為一小時內
        $start_at = Carbon::parse($srp->startAt);
        $end_at   = Carbon::parse($srp->endAt);
        $minutes  = $start_at->diffInMinutes($end_at, true);

        $s_time = $start_at->format('Y-m-d H:i:s');
        $e_time = $end_at->format('Y-m-d H:i:s');
        if ($minutes > 60) {
            // 時間超過, 把開始時間往回拉
            $s_time = $end_at->subMinutes(60)->format('Y-m-d H:i:s');
        }

        $params = [
            'random'    => $random,
            'startTime' => $s_time,
            'endTime'   => $e_time,
            'agent'     => $this->config->agent,
        ];

        return $callback($this->doSyncReport($params, $url));
    }

    private function doSyncReport($params, $url)
    {
        // $callBackFeedback = new SyncCallBackFeedback();

        $result = $this->doSendProcess($params, $url);

        if ($result->error_code === 'OK') {
            $rows = $result->histories;

            $data = [];

            foreach ($rows as $row) {
                $data[] = $this->makeSyncCallBackParameter($row);
            }

            return $data;
        }

        throw new SyncException(get_class($this), 'sync error! error code : ' . $result->error_code, $this->errorMessage[$result->error_code]);
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid         = $row->betNum;
        $callBackParam->reportAt    = $row->betTime;
        $callBackParam->username    = $row->client;
        $callBackParam->table       = ($row->tableName ?? '') . '-' . ($row->commission ?? '');
        $callBackParam->round       = $row->gameRoundId;
        $callBackParam->gameCode    = $row->gameType;
        $callBackParam->betAt       = $row->betTime;
        $callBackParam->betAmount   = $row->betAmount;
        $callBackParam->validAmount = $row->validAmount;
        $callBackParam->winAmount   = $row->winOrLoss + $row->betAmount;
        $callBackParam->gameAt      = $row->betTime;
        $callBackParam->ip          = $row->ip ?? '0.0.0.0';
        $callBackParam->content     = [
            'gameType'   => $row->gameType,         // 游戏类型
            'betType'    => $row->betType,          // 投注类型
            'gameResult' => $row->gameResult,       // 开牌结果
            'appType'    => $row->appType ?? null,  // 客户端类型
            'betMethod'  => $row->betMethod ?? null,// 下注方法
        ];
        $status = [
            0 => Report::STATUS_COMPLETED,
            1 => Report::STATUS_CANCEL,
        ];

        $callBackParam->status = $status[$row->state];

        return $callBackParam;
    }

    private function doSendProcess($params, $url)
    {
        $fullParams = $this->setParams($params);

        // $response = $this->post($url, $fullParams, false);
        $response = $this->get($url . '?' . $fullParams, $fullParams, false);

        $result = json_decode($response);

        // 如果解不開，就直接把錯誤丟回去
        if ($result === null) {
            throw new JSONException(get_class($this), 'error on JSON decode !', $result);
        }

        return $result;
    }

    private function setParams($params)
    {
        $real_param = http_build_query($params);
        $data       = self::encryptText($real_param, $this->config->ALLBET_DES_KEY);
        $signParam  = $data . $this->config->ALLBET_MD5_KEY;
        $sign       = base64_encode(md5($signParam, true));
        $fullParams = [
            'data'       => $data,
            'sign'       => $sign,
            'propertyId' => $this->config->ALLBET_PROPERTY_ID,
        ];

        return http_build_query($fullParams);
    }

    private static function pkcs5Pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
    // AAAAAAAAAAA= 待確認
    public static function encryptText($string, $key)
    {
        $key    = base64_decode($key);
        $string = self::pkcs5Pad($string, 8);
        $data   = openssl_encrypt($string, 'DES-EDE3-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, base64_decode('AAAAAAAAAAA='));
        $data   = base64_encode($data);

        return $data;
    }
}
