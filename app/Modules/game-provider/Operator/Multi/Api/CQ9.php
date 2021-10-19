<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Report;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\CreateMemberException;
use GameProvider\Exceptions\GameListException;
use GameProvider\Exceptions\LaunchGameException;
use GameProvider\Exceptions\TransferException;
use GameProvider\Operator\BaseApi;
use GameProvider\Operator\Feedback\BalanceFeedback;
use GameProvider\Operator\Feedback\LaunchGameFeedback;
use GameProvider\Operator\Feedback\MemberFeedback;
use GameProvider\Operator\Feedback\TransferFeedback;
use GameProvider\Operator\Multi\BaseMultiWalletInterface;
use GameProvider\Operator\Multi\Config\CQ9ConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
// use MultiWallet\Feedback\SyncCallBackFeedback;
use GameProvider\Operator\Params\TransferParameter;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class CQ9 extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $usertoken = null;

    // public $curlHeader;

    protected $errorMessage = [
        '0'   => 'success',
        '1'   => 'Insufficient balance.',
        '2'   => 'Player not found.',
        '3'   => 'Token invalid.',
        '4'   => 'Authorization invalid.',
        '5'   => 'Bad parameters.',
        '6'   => 'Already has same account.',
        '7'   => 'Method not allowed.',
        '8'   => 'Data not found.',
        '9'   => 'MTCode duplicate.',
        '10'  => 'Time format error.',
        '11'  => 'Query time is out of range.',
        '12'  => 'Time zone must be UTC-4.',
        '13'  => 'Game is not found.',
        '14'  => 'Your account or password is incorrect.',
        '15'  => 'Account or password must use the following characters:a-z A-Z 0-9',
        '23'  => 'Game is under maintenance.',
        '24'  => 'Account too long.',
        '28'  => 'Currency is not support.',
        '29'  => 'No default pool type',
        '31'  => 'Currency does not match Agent’s currency.',
        '100' => 'Something wrong.',
        '101' => 'Auth service error.',
        '102' => 'User service error.',
        '103' => 'Transaction service error',
        '104' => 'Game Manager service error',
        '105' => 'Wallet service error.',
        '106' => 'Tviewer service error.',
        '107' => 'Orderview service error.',
        '108' => 'Report service error.',
        '109' => 'Notice service error.',
        '110' => 'Promote service error.',
        '111' => 'PromoteRed service error.',
        '112' => 'LuckyBag service error.',
        '113' => 'Rich service error.',
        '200' => 'This owner has been frozen.',
        '201' => 'This owner has been disable.',
        '202' => 'This player has been disable.',
    ];

    public function __construct(array $config)
    {
        $this->config = new CQ9ConfigConstract();

        $this->config->apiUrl   = Str::finish($config['apiUrl'], '/');
        $this->config->token    = $config['token'];
        $this->config->language = $config['language'];
        $this->config->app      = $config['app'];
        $this->config->detect   = $config['detect'];
    }

    public function __set($name, $val
    ) {
        $this->$name = [
            'Content-Type:application/x-www-form-urlencoded',
        ];
    }

    /**
     * 獲取遊戲列表.
     *
     * @return void
     */
    public function getGameList()
    {
        $gameList    = [];
        $hallsUrl    = $this->config->apiUrl.'gameboy/game/halls';
        $gamehallUrl = $this->config->apiUrl.'gameboy/game/list/';
        $halls       = $this->get($hallsUrl, null);

        if ($halls->status->code != 0) {
            throw new GameListException(get_class($this), 'Error when start getting game list error code : '.$halls->status->code, $this->errorMessage[$halls->status->code]);
        }

        foreach ($halls->data as $key => $val) {
            $params['gamehall'] = $val->gamehall;
            $list               = $this->get($gamehallUrl, $params);
            if ($list->status->code != 0) {
                throw new GameListException(get_class($this), 'Error when start getting game list error code : '.$list->status->code, $this->errorMessage[$list->status->code]);
            }
            $gameList[$key] = $list;
        }

        return $gameList;
    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $params = [
            'account'  => $member->username,
            'password' => $member->password,
            'nickname' => $member->nickname,
        ];

        $url    = $this->config->apiUrl.'gameboy/player';
        $result = $this->get($url, $params);

        if ($result->status->code != 0) {
            throw new CreateMemberException(get_class($this), 'createMember Error! error code : '.$result->status->code, $this->errorMessage[$result->status->code]);
        } else {
            $memberFeedback              = new MemberFeedback();
            $memberFeedback->extendParam = $result->data->account;

            return $memberFeedback;
        }
    }

    /**
     * 存款
     * 必須先查餘額，然後送出交易，再查餘額確認錢是不是真的進去了.
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $uuid5 = Uuid::uuid5(Uuid::NAMESPACE_DNS, 'php.net');

        $params = [
            'account' => $transfer->member->username,
            'mtcode'  => $uuid5->toString(),
            'amount'  => $transfer->amount,
        ];

        $url = $this->config->apiUrl.'gameboy/player/deposit';

        $result = $this->post($url, $params);

        if ($result->status->code != 0) {
            throw new TransferException(get_class($this), 'deposit Error! error code : '.$result->status->code, $this->errorMessage[$result->status->code]);
        } else {
            $transferFeedback = new TransferFeedback();

            $moneyData = $result->data;

            $transferFeedback->balance       = $moneyData->balance;
            $transferFeedback->remote_payno  = null;
            $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }
    }

    /**
     * 提款.
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $uuid5 = Uuid::uuid5(Uuid::NAMESPACE_DNS, 'php.net');

        $params = [
            'account' => $transfer->member->username,
            'mtcode'  => $uuid5->toString(),
            'amount'  => $transfer->amount,
        ];

        $url = $this->config->apiUrl.'gameboy/player/withdraw';

        $result = $this->post($url, $params);

        if ($result->status->code != 0) {
            throw new TransferException(get_class($this), 'withdraw Error! error code : '.$result->status->code, $this->errorMessage[$result->status->code]);
        } else {
            $transferFeedback = new TransferFeedback();

            $moneyData = $result->data;

            $transferFeedback->balance       = $moneyData->balance;
            $transferFeedback->remote_payno  = null;
            $transferFeedback->response_code = $this->reponseCode;

            return $transferFeedback;
        }
    }

    /**
     * 會員登入（取得遊戲路徑）.
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $loginUrl = $this->config->apiUrl.'gameboy/player/login';
        $gameUrl  = $this->config->apiUrl.'gameboy/player/gamelink';
        $params   = [
            'account'  => $launchGameParams->member->username,
            'password' => $launchGameParams->member->password,
        ];
        $result = $this->post($loginUrl, $params);

        if ($result->status->code != 0) {
            throw new LaunchGameException(get_class($this), 'launchGame Error! error code : '.$result->status->code, $this->errorMessage[$result->status->code]);
        } else {
            $lang = $this->getLang($launchGameParams);

            $params = [
                'usertoken' => $result->data->usertoken,
                'gamehall'  => $launchGameParams->gamehall,
                'gamecode'  => $launchGameParams->gameId,
                'gameplat'  => ($launchGameParams->device == 'mobile') ?? 'web',
                'lang'      => $lang,
                'app'       => $this->config->app,
                'detect'    => $this->config->detect,
            ];

            $result = $this->post($gameUrl, $params);

            if ($result->status->code != 0) {
                throw new LaunchGameException(get_class($this), 'launchGame Error! error code : '.$result->status->code, $this->errorMessage[$result->status->code]);
            } else {
                $launchGameFeedback                = new LaunchGameFeedback();
                $launchGameFeedback->gameUrl       = $result->data->url;
                $launchGameFeedback->mobileGameUrl = $result->data->url;
                $launchGameFeedback->token         = $result->data->token;
                $launchGameFeedback->response_code = $this->reponseCode;

                return $launchGameFeedback;
            }
        }
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
            'account' => $member->username,
        ];

        $url = $this->config->apiUrl.'gameboy/player/balance';

        $result = $this->get($url, $params);

        if ($result->status->code != 0) {
            throw new BalanceException(get_class($this), 'get balance error! error code : '.$result->status->code, $this->errorMessage[$result->status->code]);
        } else {
            $balanceFeedback                = new BalanceFeedback;
            $moneyData                      = $result->data;
            $balanceFeedback->balance       = $moneyData->balance;
            $balanceFeedback->response_code = $this->reponseCode;

            return $balanceFeedback;
        }
    }

    /**
     * 同步注單(取回時間區段的所有注單).
     *
     * @return void
     */
    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $params = [
            'starttime' => $this->getUTC4Date($srp->startAt),
            'endtime'   => $this->getUTC4Date($srp->endAt),
            'page'      => 1,
            'pagesize'  => 20000,
        ];

        return $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
        $url    = $this->config->apiUrl.'gameboy/order/view';
        $result = $this->get($url, $params, false);

        if ($result->status->code === '0') {
            $rows = $result->data->Data;
            $data = [];

            foreach ($rows as $row) {
                $data[] = $this->makeSyncCallBackParameter($row);
            }
            if ($result->data->TotalSize > $params['page']) {
                $params['page'] = $params['page'] + 1;
                $data           = array_merge($data, $this->doSyncReport($params));
            }

            return $data;
        }
        throw new SyncException(get_class($this), 'sync error! error code : '.$result->status->code, $this->errorMessage[$result->status->code]);
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid         = $row->round;
        $callBackParam->username    = $row->account;
        $callBackParam->betAmount   = $row->bet;
        $callBackParam->validAmount = $row->validbet;
        $callBackParam->gameCode    = $row->gamecode;
        $callBackParam->winAmount   = $row->win;
        $callBackParam->betAt       = $this->getAppTimezoneDate($row->bettime);
        $callBackParam->reportAt    = $this->getAppTimezoneDate($row->bettime);
        $callBackParam->round       = $row->round;
        $callBackParam->content     = $row->detail;
        $callBackParam->status      = Report::STATUS_COMPLETED;

        return $callBackParam;
    }

    /**
     * 取得遊戲語系.
     *
     * @return string
     */
    private function getLang(LaunchGameParameter $launchGameParams)
    {
        if ($launchGameParams->member->language != null) {
            switch ($launchGameParams->member->language) {
                case 'zh-Hans':
                    return 'zh-cn';
                    break;
                case 'zh-Hant':
                    return 'zh-tw';
                    break;
                default:
                    return $this->config->language;
                    break;
            }
        }

        return $this->config->language;
    }

    /**
     * 將遊戲商的時間格式 轉回系統設定的時間.
     *
     *  @param string $datetime
     * @return string
     */
    private function getAppTimezoneDate($datetime)
    {
        $date  = date('Y-m-d\TH:i:sP', strtotime($datetime) + (4 * 3600));
        $given = new \DateTime($date, new \DateTimeZone('UTC'));
        $given->setTimezone(new \DateTimeZone('Asia/Taipei'));

        return $given->format('Y-m-d H:i:s');
    }

    /**
     * 將系統時間格式 轉回遊戲商的時間
     * RFC3339 UTC-4.
     *
     * @param string $datetime
     * @return string
     */
    private function getUTC4Date($datetime)
    {
        $given = new \DateTime($datetime, new \DateTimeZone(config('app.timezone')));
        $given->setTimezone(new \DateTimeZone('UTC'));
        $date = $given->format("Y-m-d\TH:i:sP");

        return date('Y-m-d\TH:i:sP', strtotime($date) - (4 * 3600));
    }
}
