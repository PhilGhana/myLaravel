<?php

namespace MultiWallet\Api;

use MultiWallet\Api\Config\DDFConfigConstract;

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
use Carbon\Carbon;

class DDF extends BaseApi implements BaseMultiWalletInterface
{
    protected $version = 'v2';

    protected $config;

    protected $errorMessage = [
        '-1'    => 'exception failed',
        '101'   => 'invalid key parameter',
        '102'   => 'authorization failed',
    ];

    public function __construct(array $config)
    {
        $this->config = new IBOConfigConstract();
        $this->config->api_key = $config['api_key'];
        $this->config->username = $config['account'];
    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $apiUrl = $this->version . '/api/user/create';
        $params = [
            'key'       => $this->getKey(),
            'role'      => 'member',
            'agent'     => $this->config->username,
            'account'   => $member->username,
            'password'  => $member->password,
            'nickname'  => urlencode($member->nickname)
        ];

        $errorMsg = [
            '-2' => 'invalid role parameter',
            '-3' => 'invalid agent parameter',
            '-4' => 'invalid account parameter',
            '-5' => 'invalid password parameter',
            '-6' => 'invalid nickname parameter',
            '-7' => 'the specified account already exists',
            '-8' => 'agent not exist',
            '-9' => 'create failed',
        ];
        $this->errorMessage = array_merge($this->errorMessage, $errorMsg);

        $memberFeedback = new MemberFeedback();
        $result = $this->doSendProcess($memberFeedback, $apiUrl, $params);

        if ($result instanceof MemberFeedback) {
            return $result;
        }

        if ($result['Code'] == '1') {
            $memberFeedback->extendParam = $result['Data']['User'];

            return $memberFeedback;
        }

        $memberFeedback->error_code = $result['Code'];
        $memberFeedback->error_msg = $this->errorMessage[$result['Code']];

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
        return $this->transaction($transfer);
    }

    /**
     * 提款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        return $this->transaction($transfer);
    }

    /**
     * 資金轉帳
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    private function transaction(TransferParameter $transfer)
    {
        $apiUrl = $this->version . '/api/transfer';
        $payno = md5($transfer->member->playerId . time());
        $params = [
            'key'       => $this->getKey(),
            'user'      => $transfer->member->username,
            'trans_id'  => $payno,
            'amount'    => $transfer->amount,
        ];

        switch (__FUNCTION__) {
            case 'deposit':
                $params['type'] = 3;
                break;

            case 'withdraw':
                $params['type'] = 4;
                break;
        }

        $errorMsg = [
            '2'     => '會員餘額不足',
            '3'     => '上層餘額不足',
            '4'     => '交易失敗',
            '-2'    => 'invalid user parameter',
            '-3'    => 'invalid trans_id parameter',
            '-4'    => 'invalid amount parameter',
            '-5'    => 'invalid type parameter',
            '-6'    => 'the specified transfer_id already exists',
            '-7'    => 'user not exist',
            '-8'    => 'permission denied',
            '-9'    => 'user in summarize please wait',
            '-10'   => 'user need offline',
            '-11'   => 'transfer failed',
        ];
        $this->errorMessage = array_merge($this->errorMessage, $errorMsg);

        $transferFeedback = new TransferFeedback();
        $result = $this->doSendProcess($transferFeedback, $apiUrl, $params);

        if ($result instanceof TransferFeedback) {
            return $result;
        }

        if ($result['Code'] == '1') {
            if ($result['Data']['Status'] == '1') {
                $moneyData = $result['Data'];
                $transferFeedback->balance = null;
                $transferFeedback->remote_payno = $moneyData->TransId;
                $transferFeedback->response_code = $this->reponseCode;
            } else {
                $transferFeedback->error_code = $result['Data']['Status'];
                $transferFeedback->error_msg = $this->errorMessage[$result['Data']['Status']];
            }

            return $transferFeedback;
        }

        $transferFeedback->error_code = $result['Code'];
        $transferFeedback->error_msg = $this->errorMessage[$result['Code']];

        return $transferFeedback;
    }

    /**
     * 取得會員餘額
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        $apiUrl = $this->version . '/api/user/real';
        $params = [
            'key'   => $this->getKey(),
            'user'  => $member->username,
        ];

        $errorMsg = [
            '-2'    => 'invalid user parameter',
            '-3'    => 'user not exist',
            '-4'    => 'user permission denied',
            '-5'    => 'permission denied',
            '-6'    => 'sync user credit failed',
        ];
        $this->errorMessage = array_merge($this->errorMessage, $errorMsg);

        $balanceFeedback = new BalanceFeedback();
        $result = $this->doSendProcess($balanceFeedback, $apiUrl, $params);

        if ($result instanceof BalanceFeedback) {
            return $result;
        }

        if ($result['Code'] == '1') {
            $balanceFeedback->balance = $result['Data']['Credit'];
            $balanceFeedback->response_code = $this->reponseCode;

            return $balanceFeedback;
        }

        // 發生錯誤
        $balanceFeedback->error_code = $result['Code'];
        $balanceFeedback->error_msg = $this->errorMessage[$result['Code']];

        return $balanceFeedback;
    }

    /**
     * 查詢注單歷程 by Time
     *
     * @return void
     */
    public function syncReport(SyncReportParameters $srp, callable $callback)
    {
        $params = [
            'key'           => $this->getKey(),
            'start_time'    => Carbon::parse($srp->startAt)->timestamp,
            'end_time'      => Carbon::parse($srp->endAt)->timestamp,
            'page'          => 1,
            'limit'         => 1000,
        ];
        $callback($this->doSyncReport($params));
    }

    /**
     * 取得驗證 KEY
     */
    private function getKey()
    {
        $now = date('His');
        $key_md5 = md5($this->config->api_key . $now);

        return $this->config->username . $key_md5 . $now;
    }

    private function doSendProcess($feedback, $apiUrl, $params)
    {
        $response = $this->get($apiUrl, $params);
        $result = response['body'];

        // 肯定出問題了
        if ($this->reponseCode != 200) {
            $feedback->error_code = static::RESPONSE_ERROR;
            $feedback->response_code = $this->reponseCode;
            $feedback->error_msg = '對方似乎報錯:' . $this->reponseCode;

            return $feedback;
        }

        return $result;
    }

    private function doSyncReport($params)
    {
        $errorMsg = [
            '-2'    => 'bet not exist',
            '-3'    => 'invalid start_time parameter',
            '-4'    => 'invalid end_time parameter',
            '-5'    => 'invalid page parameter',
            '-6'    => 'invalid limit parameter',
            '-7'    => 'invalid date range selection',
            '-8'    => 'query time interval is too large',
            '-9'    => 'invalid platform parameter',
            '-10'   => 'this platform not supply list',
        ];
        $this->errorMessage = array_merge($this->errorMessage, $errorMsg);

        $apiUrl = $this->version . '/api/bet/record/time';
        $callBackFeedback = new SyncCallBackFeedback();
        $result = $this->doSendProcess($callBackFeedback, $apiUrl, $params);

        if ($result instanceof SyncCallBackFeedback) {
            return $result;
        }

        if ($result['Code'] == '1') {
            $rows = $result['Data'];
            $data = [];

            foreach ($rows as $row) {
                $data[] = $this->makeSyncCallBackParameter($row);
            }

            if (count($rows) > $params['page']) {
                $params['page'] = $params['page'] + 1;
                $data = array_merge($data, $this->doSyncReport($params));
            }

            return $data;
        }

        $callBackFeedback->error_code = ['Code'];
        $callBackFeedback->error_msg = $this->errorMessage[['Code']];

        return $callBackFeedback;
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid = $row['BetId'];
        $callBackParam->username = $row['User'];
        $callBackParam->betAmount = $row['BetAmount'];
        $callBackParam->validAmount = $row['BetValid'];
        $callBackParam->betAt = $row['BetAt'];
        $callBackParam->content = $row['GameRecord'];
        $callBackParam->round = $row['GameId'];
        $callBackParam->table = $row['TableId'];
        // game_id 預設為 1
        $callBackParam->gameCode = '1';

        switch ($row['Status']) {
            // 下注中
            case '00':
            case '01':
            case '05':
                $callBackParam->status = Report::STATUS_BETTING;
                break;

            // 已開獎
            case '02':
            case '06':
                $callBackParam->status = Report::STATUS_SETTLE;
                break;

            // 已結算
            case '07':
                $callBackParam->status = Report::STATUS_COMPLETED;
                break;

            // 已取消
            case '03':
            case '08':
                $callBackParam->status = Report::STATUS_CANCEL;
                break;

            // 注單錯誤
            default:
                $callBackParam->status = Report::STATUS_ERROR;
                break;
        }

        return $callBackParam;
    }
}
