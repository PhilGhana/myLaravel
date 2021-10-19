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
use GameProvider\Operator\Multi\Config\WMConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
// use MultiWallet\Feedback\SyncCallBackFeedback;

use GameProvider\Operator\Params\TransferParameter;

class WM extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $token = null;

    protected $errorMessage = [
        '0'     => '新增成功',
        '101'   => '会员身分认证错误',
        '102'   => '时间格式错误',
        '103'   => '代理商ID与识别码格式错误',
        '104'   => '新增会员资料错误,此帐号已被使用!!',
        '106'   => '新增资料错误',
        '107'   => '操作成功，但未搜寻到数据',
        '900'   => '查无此函数',
        '901'   => '转点失败',
        '911'   => '维护中',
        '10201' => '此功能仅能查询一天内的报表，您已超过上限',
        '10202' => '时间戳异常,超过30秒',
        '10301' => '代理商ID为空,请检查(vendorId)',
        '10302' => '没有这个代理商ID',
        '10303' => '有此代理商ID,但代理商代码(signature)错误',
        '10304' => '代理商代码(signature)为空',
        '10305' => '代理商已被停用登入或下注',
        '10404' => '帐号长度过长',
        '10405' => '帐号长度过短',
        '10406' => '密码长度过短',
        '10407' => '密码长度过长',
        '10409' => '姓名长度过长',
        '10410' => '会员上笔交易未成功，请联系客服人员解锁',
        '10411' => '請於30秒後再試',
        '10418' => '請於10秒後再試',
        '10419' => '筹码格式错误(请用逗号隔开)',
        '10420' => '筹码个数错误(介于5-10个)',
        '10421' => '筹码种类错误',
        '10422' => '帐号只接受英文、数字、下划线与@',
        '10501' => '查无此帐号,请检查',
        '10502' => '帐号名不得为空',
        '10503' => '密码不得为空',
        '10504' => '此帐号的密码错误',
        '10505' => '此帐号已被停用',
        '10507' => '此账号非此代理下线，不能使用此功能',
        '10508' => '密码不得为空',
        '10509' => '姓名不得为空',
        '10511' => '修改密码与原密码相同',
        '10512' => '帐号密码格式错误',
        '10601' => '限額未開放，請檢查',
        '10705' => '参数错误或未填写',
        '10801' => '加扣点不得为零',
        '10802' => '加扣点为空，或未设置(money)参数',
        '10803' => '加扣点不得为汉字',
        '10804' => '不得5秒內重复转帐',
        '10805' => '转账失败，该账号余额不足',
        '10806' => '转账失败，账户代理已超过信用额度',
        '10807' => '转帐失败，该笔单号已存在',
        '10808' => '转帐失败,一分钟内转帐次数超过10次,帐号已锁定',
        '10810' => '连线异常，交易未成功',
    ];

    public function __construct(array $config)
    {
        $this->config = new WMConfigConstract();

        $this->config->apiUrl    = $config['apiUrl'];
        $this->config->vendorId  = $config['vendorId'];
        $this->config->signature = $config['signature'];
        $this->config->syslang   = $config['syslang'];
        $this->config->lang      = $config['lang'];
    }

    /**
     * 獲取遊戲列表.
     *
     * @return void
     */
    public function getGameList()
    {
    }

    /**
     * 建立會員
     *
     * @return void
     */
    public function createMember(MemberParameter $member)
    {
        $apiUrl = $this->config->apiUrl;

        $params = [
            'cmd'       => 'MemberRegister',
            'user'      => $member->playerId,
            'password'  => $member->password,
            'username'  => $member->playerId,
            'vendorId'  => $this->config->vendorId,
            'signature' => $this->config->signature,
            'timestamp' => time(),
            'syslang'   => $this->config->syslang,
        ];

        $memberFeedback = new MemberFeedback();

        $result = $this->doSendProcess($memberFeedback, $apiUrl, $params);

        if ($result->errorCode != 0) {
            throw new CreateMemberException(get_class($this), 'create member error! error code : '.$result->errorCode, $this->errorMessage[$result->errorCode]);
        }

        return $memberFeedback;
    }

    /**
     * 存款.
     *
     * @return void
     */
    public function deposit(TransferParameter $transfer)
    {
        $apiUrl = $this->config->apiUrl;

        $params = [
            'cmd'       => 'ChangeBalance',
            'vendorId'  => $this->config->vendorId,
            'signature' => $this->config->signature,
            'timestamp' => time(),
            'syslang'   => $this->config->syslang,
            'user'      => $transfer->member->playerId,
            'money'     => $transfer->amount,
            // "order"     => $this->GUID(),
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($transferFeedback, $apiUrl, $params);

        if ($result->errorCode != 0) {
            throw new TransferException(get_class($this), 'deposit error! error code : '.$result->errorCode, $this->errorMessage[$result->errorCode]);
        }

        $data = $result->result;
        if ($data) {
            $transferFeedback->remote_payno = (isset($data->orderId)) ? $data->orderId : null;
            $transferFeedback->balance      = (isset($data->Cash)) ? $data->Cash : null;
        }

        return $transferFeedback;
    }

    /**
     * 提款.
     *
     * @return void
     */
    public function withdraw(TransferParameter $transfer)
    {
        $apiUrl = $this->config->apiUrl;

        $params = [
            'cmd'       => 'ChangeBalance',
            'vendorId'  => $this->config->vendorId,
            'signature' => $this->config->signature,
            'timestamp' => time(),
            'syslang'   => $this->config->syslang,
            'user'      => $transfer->member->playerId,
            'money'     => $transfer->amount * -1,
            // "order"     => $this->GUID(),
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess($transferFeedback, $apiUrl, $params);

        if ($result->errorCode != 0) {
            throw new TransferException(get_class($this), 'withdraw error! error code : '.$result->errorCode, $this->errorMessage[$result->errorCode]);
        }

        $data = $result->result;
        if ($data) {
            $transferFeedback->remote_payno = (isset($data->orderId)) ? $data->orderId : null;
            $transferFeedback->balance      = (isset($data->Cash)) ? $data->Cash : null;
        }

        return $transferFeedback;
    }

    /**
     * 會員登入（取得遊戲路徑）.
     *
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $apiUrl = $this->config->apiUrl;

        $params = [
            'cmd'       => 'SigninGame',
            'vendorId'  => $this->config->vendorId,
            'signature' => $this->config->signature,
            'timestamp' => time(),
            'syslang'   => $this->config->syslang,
            'user'      => $launchGameParams->member->playerId,
            'password'  => $launchGameParams->member->password,
            'lang'      => $this->config->lang,
            // "isTest"    => $launchGameParams->$fun,
        ];

        $launchGameFeedback = new LaunchGameFeedback();

        $result = $this->doSendProcess($launchGameFeedback, $apiUrl, $params);

        if ($result->errorCode != 0) {
            throw new LaunchGameException(get_class($this), 'launchGame error! error code : '.$result->errorCode, $this->errorMessage[$result->errorCode]);
        }
        $launchGameFeedback->gameUrl = $result->result;

        return $launchGameFeedback;
    }

    /**
     * 取得會員餘額.
     *
     * @return void
     */
    public function getBalance(MemberParameter $member)
    {
        $apiUrl = $this->config->apiUrl;

        $params = [
            'cmd'       => 'GetBalance',
            'vendorId'  => $this->config->vendorId,
            'signature' => $this->config->signature,
            'timestamp' => time(),
            'syslang'   => $this->config->syslang,
            'user'      => $member->playerId,
        ];

        $balanceFeedback = new BalanceFeedback();

        $result = $this->doSendProcess($balanceFeedback, $apiUrl, $params);

        if ($result->errorCode != 0) {
            throw new BalanceException(get_class($this), 'get balance error! error code : '.$result->errorCode, $this->errorMessage[$result->errorCode]);
        }

        $balanceFeedback->balance = $result->result;

        return $balanceFeedback;
    }

    /**
     * 同步注單 (取得遊戲每日統計資訊(全部遊戲類型)).
     *
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $apiUrl = $this->config->apiUrl;

        $format   = 'YmdHis';
        $start_at = date($format, strtotime($srp->startAt));
        $end_at   = date($format, strtotime($srp->endAt));

        // 獲取注單
        $params = [
            'cmd'       => 'GetDateTimeReport',
            'vendorId'  => $this->config->vendorId,
            'signature' => $this->config->signature,
            'timestamp' => time(),
            'syslang'   => 1, // 固定拿英文, 辨別小費等比較好處理
            'startTime' => $start_at,
            'endTime'   => $end_at,
            'timetype'  => 0, // 0:抓下注时间, 1:抓结算时间
            'datatype'  => 2, // 0:输赢报表, 1:小费报表, 2:全部
            // gameno1  => 0, // 期数
        ];

        $result = $this->doSendProcess(null, $apiUrl, $params);

        if ($result->errorCode != 0) {
            // 排除107, 當動作成功, 沒有單的時候會跳107, 避免一直寫到log去排除
            if($result->errorCode == 107) {
                return;
            }
            throw new SyncException(get_class($this), 'syncReport error! error code : '.$result->errorCode, $this->errorMessage[$result->errorCode]);
        }

        $data = [];

        if (isset($result->result)) {
            foreach ($result->result as $row) {
                $data[] = $this->makeSyncCallBackParameter($row);
            }
        }

        return $callback($data);
    }

    private function doSendProcess($feedback, $apiUrl, $params)
    {
        // $url = $apiUrl . '?' . http_build_query([
        //     'cmd'       => $params['cmd'],
        //     'vendorId'  => $params['vendorId'],
        //     'signature' => $params['signature']
        // ]);
        $url = $apiUrl.'?'.http_build_query($params);

        $fullParams = json_encode($params);

        $response = $this->post($url, $fullParams, false);

        return json_decode($response);
    }

    private function makeSyncCallBackParameter($row)
    {
        $format = 'Y-m-d H:i:s';
        $now    = date($format);

        $callBackParam           = new SyncCallBackParameter();
        $callBackParam->mid      = $row->betId; // 注單ID
        $callBackParam->gameCode = $row->gid;
        $callBackParam->username = $row->user;  // 下注會員帳號
        $callBackParam->betAt    = $row->betTime; // 下注時間
        $callBackParam->reportAt = $row->betTime; // 結算時間
        $callBackParam->table    = $row->tableId;
        // $callBackParam->round = $row->gid;
        // $callBackParam->waterAmount = ;
        $callBackParam->betAmount   = $row->bet; // 下注時間金額
        $callBackParam->validAmount = $row->validbet; // 有效下注
        $callBackParam->winAmount   = $row->winLoss + $row->bet; // 輸贏金額
        // $callBackParam->prize = ;
        // $callBackParam->tip = ;
        $callBackParam->ip     = $row->ip; //下注IP

        // 這邊檢查是不是小費
        if ($row->betResult == 'tips') {
            $callBackParam->betAmount   = 0; // 下注時間金額
            $callBackParam->validAmount = 0; // 有效下注
            $callBackParam->winAmount   = 0; // 輸贏金額

            // 這時候小費他會放在投注額內
            $callBackParam->tip = $row->bet;
        }

        $callBackParam->status  = Report::STATUS_COMPLETED;
        $callBackParam->content = [
            'gid'        => $row->gid ?? '',        // 游戏类别编号
            'betCode'    => $row->betCode ?? '',    // 下注代碼
            'gameResult' => $row->gameResult ?? '', // 牌型
            'betResult'  => $row->betResult,        // 下注内容
            'result'     => $row->result ?? '',     // 下注结果
        ];

        return $callBackParam;
    }
}
