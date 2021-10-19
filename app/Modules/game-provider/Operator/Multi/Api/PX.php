<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Game;
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
use GameProvider\Operator\Multi\Config\PXConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;

class PX extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $token = null;

    protected $errorMessage = [
        '400'  => '签名参数错误',
        '401'  => '签名错误',
        '402'  => '参数错误',
        '500'  => '数据库错误',
        '1000' => '用户不存在',
        '1001' => 'money 必须>0',
        '1002' => '无此存款或提款配置',
        '1003' => '未达单笔最低限额',
        '1004' => '超过单笔最高限额',
        '1005' => '超过当日限额',
        '1006' => '订单已存在',
        '1007' => '渠道转入配额不足',
        '1008' => '渠道转出配额不足',
    ];

    public function __construct(array $config)
    {
        $this->config            = new PXConfigConstract();
        $this->config->apiUrl    = str_finish($config['apiUrl'], '/');
        $this->config->appkey    = $config['appkey'];
        // $this->config->account   = $config['account'];
        // $this->config->password  = $config['password'];
        $this->config->gameId    = $config['gameId'];
        $this->config->platid    = $config['platid'];
        $this->config->currency  = $config['currency'];
        $this->config->lang      = $config['lang'];
        // 你怎麼會覺得IP是用設定的呢?
        // $this->config->ip        = $config['ip'];
        // $this->config->subplatid = $config['subplatid'];
    }

    /**
     * 獲取遊戲列表
     *
     * @return void
     */
    public function getGameList()
    {
        // 未提供 API，所有遊戲資料寫在文件
        return [];
    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $body = [
            'account'   => $member->playerId,
            'password'  => $member->password,
            'ip'        => \Request::ip(),
            'subplatid' => 1,
        ];

        $url = 'sdk/register';

        $memberFeedback = new MemberFeedback();
        $result         = $this->doSendProcess($url, $body);
        $status         = (string) $result->status;

        // 200 注册成功，其他为失败
        if ($status != '200') {
            $msg = isset($this->errorMessage[$status]) ? $this->errorMessage[$status] : $result->desc;
            throw new CreateMemberException(get_class($this), 'create member error! error code : ' . $status, $msg);
        }

        // 用户 ID（注册成功返回的我方 ID）
        $memberFeedback->extendParam = $result->userid;

        return $memberFeedback;
    }

    /**
     * 存款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $url  = 'sdk/callback_redeemin';

        $body = [
            'account'   => $transfer->member->playerId,
            'platorder' => 'IN' .$transfer->member->playerId . time(), // 订单号(渠道方订单号、流水号,最短 3 位,最长 32 位唯一字符串)
            'money'     => $transfer->amount,
            'ts'        => time(),
            // 'moneytype' => $this->config->currency,
        ];

        $transferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess($url, $body);
        $status           = (string) $result->status;

        if ($status != '200') {
            $msg = isset($this->errorMessage[$status]) ? $this->errorMessage[$status] : $result->desc;
            throw new TransferException(get_class($this), 'deposit error! error code : ' . $status, $msg);
        }

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
        $url  = 'sdk/callback_redeemout';

        $body = [
            'account'   => $transfer->member->playerId,
            'platorder' => 'OT' .$transfer->member->playerId . time(), // 订单号(渠道方订单号、流水号,最短 3 位,最长 32 位唯一字符串)
            'money'     => $transfer->amount,
            'ts'        => time(),
            // 'moneytype' => $this->config->currency,
        ];

        $transferFeedback = new TransferFeedback();
        $result           = $this->doSendProcess($url, $body);
        $status           = (string) $result->status;

        if ($status != '200') {
            $msg = isset($this->errorMessage[$status]) ? $this->errorMessage[$status] : $result->desc;
            throw new TransferException(get_class($this), 'withdraw error! error code : ' . $status, $msg);
        }

        return $transferFeedback;
    }

    /**
     * 會員登入（取得遊戲路徑）
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $url  = 'sdk/login';

        $body = [
            'account'    => $launchGameParams->member->playerId,
            'password'   => $launchGameParams->member->password,
            'ip'         => \Request::ip(),
            'subplatid'  => 1,
            // 'maskid'     => $launchGameParams->gameId,
            // 'language'   => $this->config->lang,
            // 'moneytype'  => $this->config->currency,
        ];

        $launchGameFeedback = new LaunchGameFeedback();
        $result             = $this->doSendProcess($url, $body);
        $status             = (string) $result->status;

        if ($status != '200') {
            $msg = isset($this->errorMessage[$status]) ? $this->errorMessage[$status] : $result->desc;
            throw new LaunchGameException(get_class($this), 'launch game error! error code : ' . $status, $msg);
        }

        $launchGameFeedback->gameUrl       = $result->gameurl;
        $launchGameFeedback->mobileGameUrl = $result->gameurl;
        $launchGameFeedback->token         = $result->token;

        return $launchGameFeedback;
    }

    /**
     * 取得會員餘額
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        $url  = 'sdk/callback_balance';

        $body = [
            'account'   => $member->playerId,
            'ts'        => time(),
            // 'moneytype' => $this->config->currency,
        ];

        $balanceFeedback = new BalanceFeedback();
        $result          = $this->doSendProcess($url, $body);
        $status          = (string) $result->status;

        if ($status != '200') {
            $msg = isset($this->errorMessage[$status]) ? $this->errorMessage[$status] : $result->desc;
            throw new BalanceException(get_class($this), 'launch game error! error code : ' . $status, $msg);
        }

        $balanceFeedback->balance       = $result->money; // 余额（游戏币）

        return $balanceFeedback;
    }

    /**
     * get sign
     *
     * @param array $params
     * @return string
     */
    private function getSign($params = [])
    {
        if (!\is_array($params)) {
            throw new JSONException(get_class($this), 'error on SIGN encode !', $params);
        }

        return \MD5(\json_encode($params) . $this->config->appkey);
    }

    /**
     * get body
     *
     * @param array $params
     * @return string
     */
    private function getBody($params)
    {
        if (!\is_array($params)) {
            throw new JSONException(get_class($this), 'error on BODY encode !', $params);
        }

        return \base64_encode(\json_encode($params));
    }

    private function doSendProcess($url, $params)
    {
        $sign = $this->getSign($params);
        $urlParams = $this->createUrlParams($sign);
        $apiUrl = $this->config->apiUrl . $url . '?' . $urlParams;

        $response = $this->post($apiUrl, $this->getBody($params), false);
        $result   = json_decode($response);

        // 如果解不開，就直接把錯誤丟回去
        if ($result === null) {
            throw new JSONException(get_class($this), 'error on JSON decode !', $result);
        }

        return $result;
    }

    private function createUrlParams($sign)
    {
        $params = [
            'gameid' => $this->config->gameId,
            'platid' => $this->config->platid,
            'sign'   => $sign
        ];

        return http_build_query($params);
    }

    private function getMoneyType($language)
    {
        switch ($language) {
            case 'en':
                // US 美国
                return 102;
                break;

            case 'jp':
                // JPN 日本
                return 103;
                break;

            case 'th':
                // THA 泰国
                return 104;
                break;

            case 'ph':
                // PHI 菲律宾
                return 105;
                break;

            case 'vi':
                // VN 越南
                return 106;
                break;

            case 'ms':
                // MY 马来西亚
                return 107;
                break;

            default:
                // CHN 中国
                return 101;
                break;
        }
    }

    /**
     * 同步注單 (取回時間區段的所有注單)
     *
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $body = [
            'account'   => '', // 允许传空，模糊查询所有账号
            'subplatid' => 0,
            'start'     => Carbon::parse($srp->startAt)->format('YmdHis'),
            'end'       => Carbon::parse($srp->endAt)->format('YmdHis'),
            'curpage'   => 0,
            'perpage'   => 1000
        ];

        return $callback($this->doSyncReport($body));
    }

    private function doSyncReport($params)
    {
        $url    = 'sdk/callback_record';
        $result = $this->doSendProcess($url, $params);
        $status = (string) $result->status;

        if ($status != '200') {
            $msg = isset($this->errorMessage[$status]) ? $this->errorMessage[$status] : $result->desc;
            throw new SyncException(get_class($this), 'sync error! error code : ' . $status, $msg);
        }

        $items = $result->data;
        $data  = [];

        foreach ($items as $item) {
            array_push($data, $this->makeSyncCallBackParameter($item));
        }

        if ($result->maxpage > ($result->curpage + 1)) {
            $params['curpage'] = $params['curpage'] + 1;
            $data              = array_merge($data, $this->doSyncReport($params));
        }

        return $data;
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam              = new SyncCallBackParameter();
        $callBackParam->mid         = $row->logflag;
        $callBackParam->username    = $row->account;
        $callBackParam->betAmount   = $row->chips;
        $callBackParam->validAmount = $row->chips;
        $callBackParam->gameCode    = $row->maskid;
        $callBackParam->winAmount   = $row->aftertaxmoney + $row->chips;
        $callBackParam->table       = $row->tableid;
        $callBackParam->betAt       = $row->gameendtime; // 游戏结束时间
        $callBackParam->reportAt    = $row->gameendtime;
        $callBackParam->content     = $row->chipsEx;
        $callBackParam->status      = Report::STATUS_COMPLETED;

        return $callBackParam;
    }
}
