<?php

namespace MultiWallet\Api;

use MultiWallet\Api\Config\IMOneConfigConstract;

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

class IMOne extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = new IMOneConfigConstract();
        $this->config->merchantCode = $config['MerchantCode'];
        $this->config->currency = $config['Currency'] ?? 'CNY';
    }

    /**
     * 建立新玩家
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $params = [
                'MerchantCode' => $this->config->merchantCode,
                'PlayerId' => $member->username,
                'Password' => $member->password,
                'Currency' => $this->config->currency,
            ];

        $apiUrl = '/Player/Register';
        $memberFeedback = new MemberFeedback();
        $result = $this->sentApi($memberFeedback, $apiUrl, $params);

        if ($result instanceof MemberFeedback) {
            return $result;
        }

        // 發生錯誤
        if ($result["Code"] !== "0") {
            $memberFeedback->error_code = $result["Code"];
            $memberFeedback->error_msg = $result["Message"];

            return $memberFeedback;
        }

        // 無回傳 UID
        $memberFeedback->extendParam = $member->username;

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
            'MerchantCode' => $this->config->merchantCode,
            'PlayerId' => $member->username,
            'ProductWallet' => $member->productWallet,
        ];


        $apiUrl = '/Player/GetBalance';
        $balanceFeedback = new BalanceFeedback();
        $result = $this->sentApi($balanceFeedback, $apiUrl, $params);

        if ($result instanceof BalanceFeedback) {
            return $result;
        }

        // 發生錯誤
        if ($result["Code"] !== "0") {
            $balanceFeedback->error_code = $result["Code"];
            $balanceFeedback->error_msg = $result["Message"];

            return $balanceFeedback;
        }

        $balanceFeedback->response_code = $this->reponseCode;
        $balanceFeedback->balance = $result["Balance"];

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
        return $this->transaction($transfer);
    }

    /**
     * 提款
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
        $payno = md5($transfer->member->playerId . time());
        $params = [
            'MerchantCode' => $this->config->merchantCode,
            'PlayerId' => $transfer->member->username,
            'ProductWallet' => $transfer->member->productWallet,
            'TransactionId' => $payno,
            'Amount' => $transfer->amount,
        ];

        $apiUrl = '/Player/PerformTransfer';
        $transferFeedback = new TransferFeedback();
        $result = $this->sentApi($transferFeedback, $apiUrl, $params);

        if ($result instanceof TransferFeedback) {
            return $result;
        }

        // 發生錯誤
        if ($result["Code"] !== "0") {
            $transferFeedback->error_code = $result["Code"];
            $transferFeedback->error_msg = $result["Message"];

            return $transferFeedback;
        }

        // 未提供交易訂單編號，則回傳 TransactionId
        $transferFeedback->remote_payno = $payno;
        $transferFeedback->response_code = $this->reponseCode;

        return $transferFeedback;
    }

    /**
     * 同步注單日誌到報表
     *
     * @return void
     */
    public function syncReport(SyncReportParameters $srp, callable $callback)
    {
        switch ($srp->productWallet) {
            case '301':
            case '401':
                $params = [
                        'MerchantCode'      => $this->config->merchantCode,
                        'StartDate'         => $srp->startAt,
                        'EndDate'           => $srp->endAt,
                        'Page'              => 1,
                        'DateFilterType'    => $srp->dateFilterType ?? 1,
                        'BetStatus'         => $srp->status,
                        'ProductWallet'     => $srp->productWallet,
                        'Language'          => 'ZH-CN',
                    ];
                break;

            default:
                $params = [
                        'MerchantCode'  => $this->config->merchantCode,
                        'StartDate'     => $srp->startAt,
                        'EndDate'       => $srp->endAt,
                        'Page'          => 1,
                        'PageSize'      => 50000,
                        'ProductWallet' => $srp->productWallet,
                        'Currency'      => $this->config->currency,
                    ];
                break;
        }

        return $callback($this->doSyncReport($params));
    }

    /**
     * 發送
     *
     * @param BaseFeedback  $feedback
     * @param string        $apiUrl
     * @param array         $params
     */
    private function sentApi($feedback, $apiUrl, $params)
    {
        $response = $this->post($apiUrl, $params, false);
        $result = json_decode($response, true);

        // 如果解不開，就直接把錯誤丟回去
        if (is_null($result)) {
            $feedback->error_code = static::ENCRYPT_ERROR;
            $feedback->error_msg = $response;

            return $feedback;
        }

        // 肯定出問題了
        if ($this->reponseCode != 200) {
            $feedback->error_code = static::RESPONSE_ERROR;
            $feedback->response_code = $this->reponseCode;
            $feedback->error_msg = '對方似乎報錯:' . $this->reponseCode;

            return $feedback;
        }

        return $result;
    }

    /**
     * 撈取日誌並存到報表
     *
     * @param array $params
     */
    private function doSyncReport($params)
    {
        $data = [];
        $apiUrl = '/Report/GetBetLog';
        $dateFormat = 'yyyy-MM-dd HH.mm.ss';
        $productWallet = $params["ProductWallet"];

        $beginTime = Carbon::parse($params['StartDate']);
        $endTime = Carbon::parse($params['EndDate']);
        $start = $beginTime->copy();

        do {
            // 根據產品來選擇搜尋的時間區間
            switch ($productWallet) {
                case '301':
                case '401':
                    // 時間間隔最大為 31 天
                    $end = $start->copy()->addDays(31);
                    break;

                default:
                    // 時間間隔最大為 10 分鐘
                    $end = $start->copy()->addMinutes(10);
                    break;
            }

            if ($end->gte($endTime)) {
                $end = $endTime;
            }

            $params['StartDate'] = $start->copy()->format($dateFormat);
            $params['EndDate'] = $end->copy()->format($dateFormat);

            // 下個時間區間
            $start = $end->copy();

            $callBackFeedback = new SyncCallBackFeedback();
            $result = $this->sentApi($callBackFeedback, $apiUrl, $params);

            if ($result instanceof SyncCallBackFeedback) {
                return $result;
            }

            // 發生錯誤
            if ($result["Code"] !== "0") {
                $callBackFeedback->error_code = $result["Code"];
                $callBackFeedback->error_msg = $result["Message"];

                return $callBackFeedback;
            }

            $rows = $result["Result"];

            foreach ($rows as $row) {
                $data[] = $this->makeSyncCallBackParameter($row, $productWallet);
            }

            if ($result["Pagination"]["TotalPage"] > $params['page']) {
                $params['page'] = $params['page'] + 1;
                $data = array_merge($data, $this->doSyncReport($params));
            }
        } while ($start->lt($endTime));

        return $data;
    }

    /**
     * build callback parameter
     *
     * @param array $row
     * @param string $productWallet
     */
    private function makeSyncCallBackParameter($row, $productWallet)
    {
        $callBackParam = new SyncCallBackParameter();

        switch ($productWallet) {
            case '102':
                $callBackParam->mid = $row["SessionId"];
                $callBackParam->username = $row["PlayerName"];
                $callBackParam->gameCode = $row["GameId"];
                $callBackParam->betAmount = $row["Bet"];
                $callBackParam->winAmount = $row["Win"];
                $callBackParam->prize = $row["ProgressiveWin"];
                $callBackParam->betAt = $row["GameDate"];
                $callBackParam->reportAt = $row["GameDate"];
                break;

            case '101':
                $status = [
                        'Open' => Report::STATUS_BETTING,
                        'Cancelled' => Report::STATUS_CANCEL,
                        'Settled' => Report::STATUS_SETTLE,
                        'Closed' => Report::STATUS_COMPLETED
                    ];

                $callBackParam->mid = $row["RoundId"];
                $callBackParam->username = $row["PlayerId"];
                $callBackParam->gameCode = $row["GameId"];
                $callBackParam->round = $row["RoundId"];
                $callBackParam->betAmount = $row["BetAmount"];
                $callBackParam->winAmount = $row["WinLoss"];
                $callBackParam->prize = $row["ProgressiveWin"];
                $callBackParam->betAt = $row["DateCreated"];
                $callBackParam->reportAt = $row["LastUpdatedDate"];
                $callBackParam->status = $status[$row["Status"]];
                break;

            case '201':
                $status = [
                        'Open' => Report::STATUS_BETTING,
                        'Cancelled' => Report::STATUS_CANCEL,
                        'Settled' => Report::STATUS_SETTLE,
                        'Unsettled' => Report::STATUS_ROLLBACK
                    ];

                $callBackParam->mid = $row["BetId"];
                $callBackParam->username = $row["PlayerId"];
                $callBackParam->gameCode = $row["GameId"];
                $callBackParam->round = $row["RoundId"];
                $callBackParam->betAmount = $row["BetAmount"];
                $callBackParam->validAmount = $row["ValidBet"];
                $callBackParam->winAmount = $row["WinLoss"];
                $callBackParam->prize = $row["ProgressiveWin"];
                $callBackParam->tip = $row["Tips"];
                $callBackParam->betAt = $row["DateCreated"];
                $callBackParam->reportAt = $row["LastUpdatedDate"];
                $callBackParam->status = $status[$row["Status"]];
                break;

            case '2':
            case '4':
                $status = [
                        'Open' => Report::STATUS_BETTING,
                        'Cancelled' => Report::STATUS_CANCEL,
                        'Settled' => Report::STATUS_SETTLE,
                    ];

                $callBackParam->mid = $row["ProviderRoundId"];
                $callBackParam->username = $row["PlayerId"];
                $callBackParam->gameCode = $row["GameId"];
                $callBackParam->round = $row["ProviderRoundId"];
                $callBackParam->betAmount = $row["BetAmount"];
                $callBackParam->winAmount = $row["WinLoss"];
                $callBackParam->prize = $row["ProgressiveWin"];
                $callBackParam->betAt = $row["DateCreated"];
                $callBackParam->reportAt = $row["LastUpdatedDate"];
                $callBackParam->status = $status[$row["Status"]];
                break;

            case '502':
            case '503':
            case '504':
                $status = [
                        'Open' => Report::STATUS_BETTING,
                        'Cancelled' => Report::STATUS_CANCEL,
                        'Settled' => Report::STATUS_SETTLE,
                        'Adjusted' => Report::STATUS_CANCEL,
                    ];

                $callBackParam->mid = $row["BetId"];
                $callBackParam->username = $row["PlayerId"];
                $callBackParam->gameCode = $row["GameId"];
                $callBackParam->round = $row["ProviderRoundId"];
                $callBackParam->betAmount = $row["BetAmount"];
                $callBackParam->winAmount = $row["WinLoss"];
                $callBackParam->tip = $row["Tips"];
                $callBackParam->betAt = $row["DateCreated"];
                $callBackParam->reportAt = $row["LastUpdatedDate"];
                $callBackParam->status = $status[$row["Status"]];
                $callBackParam->content = $row["BetDetails"];
                break;

            case '602':
            case '603':
            case '604':
            case '605':
            case '606':
            case '607':
            case '608':
            case '609':
            case '610':
                $status = [
                        'Settled' => Report::STATUS_SETTLE,
                    ];

                $callBackParam->mid = $row["BetId"];
                $callBackParam->username = $row["PlayerId"];
                $callBackParam->gameCode = $row["GameId"];
                $callBackParam->round = $row["RoundId"];
                $callBackParam->betAmount = $row["BetAmount"];
                $callBackParam->validAmount = $row["ValidBet"];
                $callBackParam->winAmount = $row["WinLoss"];
                $callBackParam->betAt = $row["DateCreated"];
                $callBackParam->reportAt = $row["LastUpdatedDate"];
                $callBackParam->status = $status[$row["Status"]];
                break;

            case '702':
                $status = [
                        'Settled' => Report::STATUS_SETTLE,
                    ];

                $callBackParam->mid = $row["BetId"];
                $callBackParam->username = $row["PlayerId"];
                $callBackParam->gameCode = $row["GameId"];
                $callBackParam->round = $row["RoundId"];
                $callBackParam->betAmount = $row["BetAmount"];
                $callBackParam->validAmount = $row["ValidBet"];
                $callBackParam->winAmount = $row["WinLoss"];
                $callBackParam->prize = $row["ProgressiveWin"];
                $callBackParam->betAt = $row["DateCreated"];
                $callBackParam->reportAt = $row["LastUpdatedDate"];
                $callBackParam->status = $status[$row["Status"]];
                break;

            case '301':
                $callBackParam->mid = $row["BetId"];
                $callBackParam->username = $row["PlayerId"];
                $callBackParam->gameCode = $row["GameId"];
                $callBackParam->betAmount = $row["StakeAmount"];
                $callBackParam->validAmount = $row["MemberExposure"];
                $callBackParam->winAmount = $row["WinLoss"];
                $callBackParam->betAt = $row["WagerCreationDateTime"];
                $callBackParam->reportAt = $row["LastUpdatedDate"];
                $callBackParam->content = $row["DetailItems"];

                if ($row["IsSettled"]) {
                    $callBackParam->status = Report::STATUS_SETTLE;
                } else if ($row["IsCancelled"]) {
                    $callBackParam->status = Report::STATUS_CANCEL;
                }
                break;

            case '401':
                $callBackParam->mid = $row["BetId"];
                $callBackParam->username = $row["PlayerId"];
                $callBackParam->gameCode = $row["GameId"];
                $callBackParam->betAmount = $row["StakeAmount"];
                $callBackParam->winAmount = $row["WinLoss"];
                $callBackParam->betAt = $row["WagerCreationDateTime"];
                $callBackParam->reportAt = $row["SettlementDateTime"];
                $callBackParam->content = $row["DetailItems"];

                if ($row["IsSettled"]) {
                    $callBackParam->status = Report::STATUS_SETTLE;
                } else if ($row["IsCancelled"]) {
                    $callBackParam->status = Report::STATUS_CANCEL;
                }
                break;
        }

        return $callBackParam;
    }
}
