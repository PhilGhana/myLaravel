<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Report;
use Carbon\Carbon;
use GameProvider\Exceptions\AesException;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\CreateMemberException;
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
use GameProvider\Operator\Multi\Config\IMOneConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;
use Illuminate\Support\Str;

class IMOne extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    // 預設錯誤訊息
    protected $errorMessage = [
        '0'     => 'Successful.',
        '500'   => 'Invalid Merchant or Reseller Code.',
        '501'   => 'Unauthorized access.',
        '505'   => 'Required field cannot be empty or null.',
        '538'   => 'Setup in progress. Please contact
        support.',
        '600'   => 'Provider Internal Error.',
        '601'   => 'Unauthorized product access.',
        '612'   => 'Invalid Argument.',
        '998'   => 'System is currently unable to process
        your request. Please try again.',
        '999'   => 'System has failed to process your
        request.',
    ];

    public function __construct(array $config)
    {
        $this->config               = new IMOneConfigConstract();
        $this->config->apiUrl       = $config['ApiUrl'];
        $this->config->merchantCode = $config['MerchantCode'];
        $this->config->currency     = $config['Currency'] ?? 'CNY';
        $this->config->language     = $config['Language'];
    }

    /**
     * 建立新玩家.
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $params = [
                'MerchantCode' => $this->config->merchantCode,
                'PlayerId'     => $member->username,
                'Password'     => $member->password,
                'Currency'     => $this->config->currency,
            ];
        $errorMsg = [
            '503'   => 'Player already exists.',
            '506'   => 'Invalid player ID.',
            '507'   => 'Invalid Currency.',
            '524'   => 'Invalid Password',
            '556'   => 'Player is not eligible due to an age
            restriction of 18.',
        ];
        $this->errorMessage = array_merge($this->errorMessage, $errorMsg);

        $apiUrl         = 'Player/Register';
        $memberFeedback = new MemberFeedback();
        $result         = $this->sentApi($apiUrl, $params);
        $code           = strval($result->Code);

        // 發生錯誤
        if ($code !== '0') {
            throw new LaunchGameException(get_class($this), 'launch game error! error code : '.$code, $this->errorMessage[$code]);
        }

        // 無回傳 UID
        $memberFeedback->extendParam = $member->username;

        return $memberFeedback;
    }

    /**
     * 取得會員餘額.
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        $params = [
            'MerchantCode'  => $this->config->merchantCode,
            'PlayerId'      => $member->username,
            'ProductWallet' => $member->gameCode,
        ];
        $errorMsg = [
            '504'   => 'Player already exists.',
            '506'   => 'Invalid player ID.',
            '508'   => 'Invalid Product Wallet.',
            '557'   => 'The API is called within minimum interval
            allowed.',
        ];
        $this->errorMessage = array_merge($this->errorMessage, $errorMsg);

        $apiUrl          = 'Player/GetBalance';
        $balanceFeedback = new BalanceFeedback();
        $result          = $this->sentApi($apiUrl, $params);
        $code            = strval($result->Code);

        // 發生錯誤
        if ($code !== '0') {
            throw new BalanceException(get_class($this), 'get balance error! error code : '.$code, $this->errorMessage[$code]);
        }

        $balanceFeedback->response_code = $this->reponseCode;
        $balanceFeedback->balance       = $result->Balance;

        return $balanceFeedback;
    }

    /**
     * 存款.
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        return $this->transaction($transfer, __FUNCTION__);
    }

    /**
     * 提款.
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        return $this->transaction($transfer, __FUNCTION__);
    }

    /**
     * 資金轉帳.
     *
     * @param TransferParameter $transfer
     * @param string $functionName
     * @return TransferFeedback
     */
    private function transaction(TransferParameter $transfer, $functionName)
    {
        $payno  = md5($transfer->member->playerId.time());
        $params = [
            'MerchantCode'  => $this->config->merchantCode,
            'PlayerId'      => $transfer->member->username,
            'ProductWallet' => $transfer->member->gameCode,
            'TransactionId' => $payno,
            'Amount'        => $transfer->amount,
        ];
        $errorMsg = [
            '504'   => 'Player already exists.',
            '506'   => 'Invalid player ID.',
            '507'   => 'Invalid Currency.',
            '508'   => 'Invalid Product Wallet.',
            '509'   => 'Invalid transaction Id.',
            '510'   => 'Insufficient amount.',
            '514'   => 'Transaction Id is duplicated in IMOne
            system.',
            '516'   => 'Transaction id is not found at provider
            side.',
            '517'   => 'Transaction is being processed by
            provider.',
            '519'   => 'Invalid amount format.',
            '520'   => 'Transaction is being processed by
            IMOne system.',
            '523'   => 'Transaction Id is duplicated at
            provider side.',
            '540'   => 'Player was not created successfully
            or inactive at provider side.',
            '541'   => 'Transaction has been processed, the
            status is declined.',
            '542'   => 'Player is inactive.',
            '543'   => 'Invalid amount. Amount must be
            multiple of the currency rate.',
            '544'   => 'Transaction cannot be processed
            while player is still in game.',
            '548'   => 'Player is suspended.',
            '557'   => 'The API is called within minimum
            interval allowed.',
            '560'   => 'Other transaction is yet to be
            processed, this transaction is
            declined.',
            '603'   => 'Deposit limit for the ongoing period
            has been exceeded.',
            '604'   => 'Amount exceeds maximum deposit
            limit.',
        ];
        $this->errorMessage = array_merge($this->errorMessage, $errorMsg);

        $apiUrl           = 'Player/PerformTransfer';
        $transferFeedback = new TransferFeedback();
        $result           = $this->sentApi($apiUrl, $params);
        $code             = strval($result->Code);

        // 發生錯誤
        if ($code !== '0') {
            throw new TransferException(get_class($this), $functionName.' error! error code : '.$code, $this->errorMessage[$code]);
        }

        // 未提供交易訂單編號，則回傳 TransactionId
        $transferFeedback->remote_payno  = $payno;
        $transferFeedback->response_code = $this->reponseCode;

        return $transferFeedback;
    }

    /**
     * 同步注單日誌到報表.
     *
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
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
                        'Language'          => $this->getLocale(),
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
     * 會員登入（取得遊戲路徑）.
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $params = [
            'MerchantCode'  => $this->config->merchantCode,
            'PlayerId'      => $launchGameParams->member->username,
            'GameCode'      => $launchGameParams->member->gameCode,
            'Language'      => $this->getLocale($launchGameParams->member->language),
            'IpAddress'     => $launchGameParams->member->ip,
            'ProductWallet' => $launchGameParams->member->gameCode,
        ];
        $errorMsg = [
            '504'   => 'Player already exists.',
            '506'   => 'Invalid player ID.',
            '508'   => 'Invalid Product Wallet.',
            '518'   => 'Invalid language.',
            '521'   => 'Invalid game code.',
            '522'   => 'Invalid IP address.',
            '533'   => 'Game is not active.',
            '536'   => 'Failed to start game (app already
            running).',
            '540'   => 'Player was not created successfully or
            inactive at provider side.',
            '542'   => 'Player is inactive.',
            '546'   => 'Game is not activated to the Operator.',
            '548'   => 'Player is suspended.',
            '557'   => 'The API is called within minimum interval
            allowed.',
            '559'   => 'Invalid Tray or Tray is not supported by
            the ProductWallet.',
            '561'   => 'Invalid BetLimitID or BetLimitID is not
            supported by the ProductWallet.',
        ];
        $this->errorMessage = array_merge($this->errorMessage, $errorMsg);

        $launchGameFeedback = new LaunchGameFeedback();
        $apiUrl             = 'Game/NewLaunchGame';
        $result             = $this->sentApi($apiUrl, $params);
        $code               = strval($result->Code);

        // 發生錯誤
        if ($code !== '0') {
            throw new LaunchGameException(get_class($this), 'launch game error! error code : '.$code, $this->errorMessage[$code]);
        }

        $launchGameFeedback->gameUrl = $result->GameUrl;

        // 手機版
        $apiUrl = 'Game/NewLaunchMobileGame';
        $result = $this->sentApi($apiUrl, $params);
        $code   = strval($result->Code);

        // 發生錯誤
        if ($code !== '0') {
            throw new LaunchGameException(get_class($this), 'launch game error! error code : '.$code, $this->errorMessage[$code]);
        }

        $launchGameFeedback->mobileGameUrl = $result->GameUrl;

        return $launchGameFeedback;
    }

    /**
     * 發送
     *
     * @param string        $apiUrl
     * @param array         $params
     */
    private function sentApi($apiUrl, $params)
    {
        $apiUrl   = asset(Str::finish($this->config->apiUrl, '/').$apiUrl);
        $response = $this->post($apiUrl, $params);
        $result   = $response;

        // 發生錯誤
        if (is_null($result)) {
            throw new JSONException(get_class($this), 'error on JSON decode !', $result);
        }

        return $result;
    }

    /**
     * 撈取日誌並存到報表.
     *
     * @param array $params
     */
    private function doSyncReport($params)
    {
        $data          = [];
        $apiUrl        = 'Report/GetBetLog';
        $dateFormat    = 'yyyy-MM-dd HH.mm.ss';
        $productWallet = $params['ProductWallet'];

        $beginTime = Carbon::parse($params['StartDate']);
        $endTime   = Carbon::parse($params['EndDate']);
        $start     = $beginTime->copy();

        $errorMsg = [
            '504'   => 'Player already exists.',
            '506'   => 'Invalid player ID.',
            '507'   => 'Invalid Currency.',
            '508'   => 'Invalid Product Wallet.',
            '525'   => 'Invalid timerange, it must be within the
            configured timerange.',
            '526'   => 'StartDate can’t be later than EndDate or
            now.',
            '527'   => 'Bet details in process. Please try again.
            ',
            '528'   => 'Invalid datetime format.',
            '529'   => 'Invalid Page Size.',
            '558'   => 'No data found.',
        ];
        $this->errorMessage = array_merge($this->errorMessage, $errorMsg);

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
            $params['EndDate']   = $end->copy()->format($dateFormat);

            // 下個時間區間
            $start  = $end->copy();
            $result = $this->sentApi($apiUrl, $params);
            $code   = strval($result->Code);

            // 發生錯誤
            if ($code !== '0') {
                throw new SyncException(get_class($this), 'sync error! error code : '.$code, $this->errorMessage[$code]);
            }

            $rows = $result->Result;

            foreach ($rows as $row) {
                $data[] = $this->makeSyncCallBackParameter($row, $productWallet);
            }

            if ($result->Pagination->TotalPage > $params['page']) {
                $params['page'] = $params['page'] + 1;
                $data           = array_merge($data, $this->doSyncReport($params));
            }
        } while ($start->lt($endTime));

        return $data;
    }

    /**
     * build callback parameter.
     *
     * @param array $row
     * @param string $productWallet
     */
    private function makeSyncCallBackParameter($row, $productWallet)
    {
        $callBackParam = new SyncCallBackParameter();

        switch ($productWallet) {
            case '102':
                $callBackParam->mid       = $row->SessionId;
                $callBackParam->username  = $row->PlayerName;
                $callBackParam->gameCode  = $row->GameId;
                $callBackParam->betAmount = $row->Bet;
                $callBackParam->winAmount = $row->Win;
                $callBackParam->prize     = $row->ProgressiveWin;
                $callBackParam->betAt     = $row->GameDate;
                $callBackParam->reportAt  = $row->GameDate;
                break;

            case '101':
                $status = [
                        'Open'      => Report::STATUS_BETTING,
                        'Cancelled' => Report::STATUS_CANCEL,
                        'Settled'   => Report::STATUS_SETTLE,
                        'Closed'    => Report::STATUS_COMPLETED,
                    ];

                $callBackParam->mid       = $row->RoundId;
                $callBackParam->username  = $row->PlayerId;
                $callBackParam->gameCode  = $row->GameId;
                $callBackParam->round     = $row->RoundId;
                $callBackParam->betAmount = $row->BetAmount;
                $callBackParam->winAmount = $row->WinLoss;
                $callBackParam->prize     = $row->ProgressiveWin;
                $callBackParam->betAt     = $row->DateCreated;
                $callBackParam->reportAt  = $row->LastUpdatedDate;
                $callBackParam->status    = $status[$row->Status];
                break;

            case '201':
                $status = [
                        'Open'      => Report::STATUS_BETTING,
                        'Cancelled' => Report::STATUS_CANCEL,
                        'Settled'   => Report::STATUS_SETTLE,
                        'Unsettled' => Report::STATUS_ROLLBACK,
                    ];

                $callBackParam->mid         = $row->BetId;
                $callBackParam->username    = $row->PlayerId;
                $callBackParam->gameCode    = $row->GameId;
                $callBackParam->round       = $row->RoundId;
                $callBackParam->betAmount   = $row->BetAmount;
                $callBackParam->validAmount = $row->ValidBet;
                $callBackParam->winAmount   = $row->WinLoss;
                $callBackParam->prize       = $row->ProgressiveWin;
                $callBackParam->tip         = $row->Tips;
                $callBackParam->betAt       = $row->DateCreated;
                $callBackParam->reportAt    = $row->LastUpdatedDate;
                $callBackParam->status      = $status[$row->Status];
                break;

            case '2':
            case '4':
                $status = [
                        'Open'      => Report::STATUS_BETTING,
                        'Cancelled' => Report::STATUS_CANCEL,
                        'Settled'   => Report::STATUS_SETTLE,
                    ];

                $callBackParam->mid       = $row->ProviderRoundId;
                $callBackParam->username  = $row->PlayerId;
                $callBackParam->gameCode  = $row->GameId;
                $callBackParam->round     = $row->ProviderRoundId;
                $callBackParam->betAmount = $row->BetAmount;
                $callBackParam->winAmount = $row->WinLoss;
                $callBackParam->prize     = $row->ProgressiveWin;
                $callBackParam->betAt     = $row->DateCreated;
                $callBackParam->reportAt  = $row->LastUpdatedDate;
                $callBackParam->status    = $status[$row->Status];
                break;

            case '502':
            case '503':
            case '504':
                $status = [
                        'Open'      => Report::STATUS_BETTING,
                        'Cancelled' => Report::STATUS_CANCEL,
                        'Settled'   => Report::STATUS_SETTLE,
                        'Adjusted'  => Report::STATUS_CANCEL,
                    ];

                $callBackParam->mid       = $row->BetId;
                $callBackParam->username  = $row->PlayerId;
                $callBackParam->gameCode  = $row->GameId;
                $callBackParam->round     = $row->ProviderRoundId;
                $callBackParam->betAmount = $row->BetAmount;
                $callBackParam->winAmount = $row->WinLoss;
                $callBackParam->tip       = $row->Tips;
                $callBackParam->betAt     = $row->DateCreated;
                $callBackParam->reportAt  = $row->LastUpdatedDate;
                $callBackParam->status    = $status[$row->Status];
                $callBackParam->content   = $row->BetDetails;
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

                $callBackParam->mid         = $row->BetId;
                $callBackParam->username    = $row->PlayerId;
                $callBackParam->gameCode    = $row->GameId;
                $callBackParam->round       = $row->RoundId;
                $callBackParam->betAmount   = $row->BetAmount;
                $callBackParam->validAmount = $row->ValidBet;
                $callBackParam->winAmount   = $row->WinLoss;
                $callBackParam->betAt       = $row->DateCreated;
                $callBackParam->reportAt    = $row->LastUpdatedDate;
                $callBackParam->status      = $status[$row->Status];
                break;

            case '702':
                $status = [
                        'Settled' => Report::STATUS_SETTLE,
                    ];

                $callBackParam->mid         = $row->BetId;
                $callBackParam->username    = $row->PlayerId;
                $callBackParam->gameCode    = $row->GameId;
                $callBackParam->round       = $row->RoundId;
                $callBackParam->betAmount   = $row->BetAmount;
                $callBackParam->validAmount = $row->ValidBet;
                $callBackParam->winAmount   = $row->WinLoss;
                $callBackParam->prize       = $row->ProgressiveWin;
                $callBackParam->betAt       = $row->DateCreated;
                $callBackParam->reportAt    = $row->LastUpdatedDate;
                $callBackParam->status      = $status[$row->Status];
                break;

            case '301':
                $callBackParam->mid         = $row->BetId;
                $callBackParam->username    = $row->PlayerId;
                $callBackParam->gameCode    = $row->GameId;
                $callBackParam->betAmount   = $row->StakeAmount;
                $callBackParam->validAmount = $row->MemberExposure;
                $callBackParam->winAmount   = $row->WinLoss;
                $callBackParam->betAt       = $row->WagerCreationDateTime;
                $callBackParam->reportAt    = $row->LastUpdatedDate;
                $callBackParam->content     = $row->DetailItems;

                if ($row->IsSettled) {
                    $callBackParam->status = Report::STATUS_SETTLE;
                } elseif ($row->IsCancelled) {
                    $callBackParam->status = Report::STATUS_CANCEL;
                }
                break;

            case '401':
                $callBackParam->mid       = $row->BetId;
                $callBackParam->username  = $row->PlayerId;
                $callBackParam->gameCode  = $row->GameId;
                $callBackParam->betAmount = $row->StakeAmount;
                $callBackParam->winAmount = $row->WinLoss;
                $callBackParam->betAt     = $row->WagerCreationDateTime;
                $callBackParam->reportAt  = $row->SettlementDateTime;
                $callBackParam->content   = $row->DetailItems;

                if ($row->IsSettled) {
                    $callBackParam->status = Report::STATUS_SETTLE;
                } elseif ($row->IsCancelled) {
                    $callBackParam->status = Report::STATUS_CANCEL;
                }
                break;
        }

        return $callBackParam;
    }

    /**
     * 取得語系.
     *
     * @param string $memberLang
     * @return string
     */
    private function getLocale($memberLang = null)
    {
        $memberLang = strtolower($memberLang);
        $langs      = [
                'zh-Hant'   => 'ZH-HK',
                'zh-Hans'   => 'ZH-CN',
                'en'        => 'EN',
                'ko'        => 'ko-kr',
                'th'        => 'TH',
                'vi'        => 'VI',
                'id'        => 'ID',
                'in'        => 'ID',
            ];

        if (! is_null($memberLang) && array_key_exists($memberLang, $langs)) {
            return $langs[$memberLang];
        }

        return $langs[$this->config->language];
    }
}
