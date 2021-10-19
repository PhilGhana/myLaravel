<?php

namespace GameProvider\Operator\Multi\Api;

use App\Models\Report;
use Carbon\Carbon;
use GameProvider\Exceptions\AesException;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\CreateMemberException;
use GameProvider\Exceptions\GameListException;
use GameProvider\Exceptions\LaunchGameException;
use GameProvider\Exceptions\SyncException;
use GameProvider\Exceptions\TransferException;
use GameProvider\Operator\BaseApi;
use GameProvider\Operator\Feedback\BalanceFeedback;
use GameProvider\Operator\Feedback\LaunchGameFeedback;
use GameProvider\Operator\Feedback\MemberFeedback;
use GameProvider\Operator\Feedback\TransferFeedback;
use GameProvider\Operator\Multi\BaseMultiWalletInterface;
use GameProvider\Operator\Multi\Config\JDBConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;

class JDB extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;
    protected $ts;
    // 報表查詢間距 [Action 64] 最大間距為5分鐘
    protected $interval              = 5;
    // 存款序號
    protected $depositTransactionID  = null;
    // 提款序號
    protected $withdrawTransactionID = null;
    // 當前存款重新嘗試次數
    protected $depositTryTimes       = 0;
    // 當前提款重新嘗試次數
    protected $withdrawTryTimes      = 0;

    public function __construct(array $config)
    {
        $this->config = new JDBConfigConstract();

        $this->config->apiUrl       = $config['apiUrl'];
        $this->config->memberParent = $config['memberParent'];
        $this->config->aesKey       = $config['aesKey'];
        $this->config->aesIv        = $config['aesIv'];
        $this->config->dc           = $config['dc'];
        $this->updateTs();
    }

    public function getGameList()
    {
        $params = json_encode([
            'action'          => 49,
            'ts'              => $this->ts,
            'parent'          => $this->config->memberParent,
            'lang'            => 'en',
        ]);

        $result = $this->doSendProcess($params);

        if ($result['status'] === '0000') {
            return $result['data'];
        }
        throw new GameListException(get_class($this), 'get game list error! error code : '.$result['status'], $result['err_text']);
    }

    public function createMember(MemberParameter $member)
    {
        $params = json_encode([
            'action'          => 12,
            'ts'              => $this->ts,
            'parent'          => $this->config->memberParent,
            'uid'             => $member->playerId,
            'name'            => $member->playerId,
            'credit_allocated'=> 0,
        ]);

        $memberFeedback = new MemberFeedback();
        $result         = $this->doSendProcess($params);

        if ($result['status'] === '0000') {
            $memberFeedback->extendParam = $member->playerId;

            return $memberFeedback;
        }
        throw new CreateMemberException(get_class($this), 'create member error! error code : '.$result['status'], $result['err_text']);
    }

    public function deposit(TransferParameter $transfer)
    {
        // 當depositTransactionID有值 表示重新嘗試
        if (is_null($this->depositTransactionID)) {
            $this->depositTransactionID = substr($transfer->member->playerId, 0, 10).time();
        }
        $params        = json_encode([
            'action'          => 19,
            'ts'              => $this->ts,
            'parent'          => $this->config->memberParent,
            'uid'             => $transfer->member->playerId,
            'serialNo'        => $this->depositTransactionID,
            'allCashOutFlag'  => 0,
            'amount'          => $transfer->amount,
        ]);

        if ($transfer->amount <= 0) {
            throw new TransferException(get_class($this), 'deposit amount <= 0');
        }

        $transferFeedback   = new TransferFeedback();
        $result             = $this->doSendProcess($params);

        if ($result['status'] === '0000') {
            $transferFeedback->balance       = $result['userBalance'];
            $transferFeedback->remote_payno  = $result['serialNo'];
            $transferFeedback->response_code = $result['status'];
            $this->depositTryTimes           = 0;
            $this->depositTransactionID      = null;

            return $transferFeedback;
        }

        // 重新嘗試
        if ($this->depositTryTimes < 1) {
            $this->depositTryTimes++;
            $this->deposit($transfer);
        } else {
            throw new TransferException(get_class($this), 'deposit error! error code : '.$result['status'], $result['err_text']);
        }
    }

    public function withdraw(TransferParameter $transfer)
    {
        // 當withdrawTransactionID有值 表示重新嘗試
        if (is_null($this->withdrawTransactionID)) {
            $this->withdrawTransactionID = substr($transfer->member->playerId, 0, 10).time();
        }
        $params        = json_encode([
            'action'          => 19,
            'ts'              => $this->ts,
            'parent'          => $this->config->memberParent,
            'uid'             => $transfer->member->playerId,
            'serialNo'        => $this->withdrawTransactionID,
            'allCashOutFlag'  => 0,
            'amount'          => $transfer->amount * -1,
        ]);

        $transferFeedback   = new TransferFeedback();
        $result             = $this->doSendProcess($params);

        if ($result['status'] === '0000') {
            $transferFeedback->balance        = $result['userBalance'];
            $transferFeedback->remote_payno   = $result['serialNo'];
            $transferFeedback->response_code  = $result['status'];
            $this->withdrawTryTimes           = 0;
            $this->withdrawTransactionID      = null;

            return $transferFeedback;
        }

        // 重新嘗試
        if ($this->withdrawTryTimes < 1) {
            $this->withdrawTryTimes++;
            $this->withdraw($transfer);
        } else {
            throw new TransferException(get_class($this), 'withdraw error! error code : '.$result['status'], $result['err_text']);
        }
    }

    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $gameIdSplit = explode('_', $launchGameParams->gameId);
        $gameType    = $gameIdSplit[0];
        $gameCode    = $gameIdSplit[1];

        $isApp = false;
        if ($launchGameParams->device == 'mobile') {
            $isApp = true;
        }

        // windowMode 是否使用大廳 我們預設為2不使用 詳細請閱 LaunchGameParameter
        $params      = json_encode([
            'action'          => 11,
            'ts'              => $this->ts,
            'uid'             => $launchGameParams->member->playerId,
            'lang'            => $launchGameParams->lang,
            'gType'           => $gameType,
            'mType'           => $gameCode,
            'windowMode'      => $launchGameParams->windowMode,
            'isAPP'           => $isApp,
        ]);

        $launchGameFeedback = new LaunchGameFeedback();
        $result             = $this->doSendProcess($params);

        if ($result['status'] === '0000') {
            $launchGameFeedback->gameUrl       = $result['path'];
            $launchGameFeedback->mobileGameUrl = $result['path'];

            return $launchGameFeedback;
        }

        throw new LaunchGameException(get_class($this), 'launch game error! error code : '.$result['status'], $result['err_text']);
    }

    public function getBalance(MemberParameter $member)
    {
        $params = json_encode([
            'action'          => 15,
            'ts'              => $this->ts,
            'parent'          => $this->config->memberParent,
            'uid'             => $member->playerId,
        ]);

        $balanceFeedback = new BalanceFeedback();
        $result          = $this->doSendProcess($params);

        if ($result['status'] === '0000') {
            $balanceFeedback->balance       = $result['data'][0]['balance'];
            $balanceFeedback->response_code = $result['status'];

            return $balanceFeedback;
        }
        throw new BalanceException(get_class($this), 'get balance error! error code : '.$result['status'], $result['err_text']);
    }

    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        $now        = $this->reportDateFormat(now());
        $start      = $this->reportDateFormat($srp->startAt);
        $end        = $this->reportDateFormat($srp->endAt);
        $dateOver2H = $this->reportDateFormat(now(), 120);
        $interval   = $this->interval;

        // 只能查3分前的帳 避免伺服器秒差我們抓4分
        if ($now == $end) {
            $start = $this->reportDateFormat($srp->startAt, 4);
            $end   = $this->reportDateFormat($srp->endAt, 4);
        }

        $first     = true;
        $diff      = Carbon::parse($start)->diffInMinutes(Carbon::parse($end), false);

        // 同步報表預設 10幾分鐘 會查一次
        // 同步報表如果掛掉 間隔很久才重啟 也會導致查詢間隔過長 只能先手動補單再啟用同步
        // 當手動輸入查詢時 避免輸入過長間隔 導致timeout
        if ($diff > 15) {
            throw new SyncException(get_class($this), 'report error! query date interval over 2 houer');
        }

        $data      = [];
        do {
            // 是否超過 當下兩小時之後
            $over      = false;
            // 是否有從兩時小內 跨越到兩小時後 的行為
            $boundary  = false;
            if (! $first) {
                $start = $end;
            }

            $overtime  = 120 - Carbon::parse($start)->diffInMinutes(Carbon::parse($now), false);
            if ($overtime < 0) {
                $overtime = $overtime * -1;
                $over     = true;
                if ($overtime <= $interval) {
                    $boundary = true;
                    $end      = Carbon::parse($start)->addMinutes($overtime)->format('d-m-Y H:i:00');
                }
            }

            if ($diff >= $interval && ! $boundary) {
                $end     = Carbon::parse($start)->addMinutes($interval)->format('d-m-Y H:i:00');
                if ($end == $now) {
                    $end = Carbon::parse($end)->subMinutes(4)->format('d-m-Y H:i:00');
                }
            }
            $data = array_merge($data, $this->getReport($start, $end, $over));
            if (! $boundary && $overtime > $interval) {
                $diff -= $interval;
            } else {
                $diff -= $overtime;
            }
            $first = false;
            // 太快呼叫 對方會噴錯
            sleep(30);
        } while ($diff >= $interval);

        return $callback($data);
    }

    // 跟遊戲商拿報表
    public function getReport($start, $end, $over = false)
    {
        // 29 => 只能查兩小時內, 64 => 只能查兩小時後 60天內的訊息
        $action = 29;
        if ($over) {
            $action = 64;
        }
        $this->updateTs();

        $params      = json_encode([
            'action'          => $action,
            'ts'              => $this->ts,
            'parent'          => $this->config->memberParent,
            'starttime'       => $start,
            'endtime'         => $end,
        ]);
        $data = [];

        $result             = $this->doSendProcess($params);

        if ($result['status'] === '0000') {
            foreach ($result['data'] as $key => $value) {
                $data[] = $this->makeSyncCallBackParameter($value);
            }

            return $data;
        }

        throw new SyncException(get_class($this), 'sync report error! error code : '.$result['status'].'  '.$result['err_text']);
    }

    private function makeSyncCallBackParameter($row)
    {
        $tz            = config('app.timezone');

        $callBackParam = new SyncCallBackParameter();

        $callBackParam->gameCode    = $row['gType'].'_'.$row['mtype'];
        $callBackParam->mid         = $row['seqNo'];
        $callBackParam->username    = $row['playerId'];
        if (array_key_exists('gambleBet', $row) && $row['gambleBet'] != 0) {
            $callBackParam->betAmount   = $row['gambleBet'] * -1;
            $callBackParam->validAmount = $row['gambleBet'] * -1;
        } else {
            $callBackParam->betAmount   = $row['bet'] * -1;
            $callBackParam->validAmount = $row['bet'] * -1;
        }
        $callBackParam->winAmount   = $row['total'];
        $callBackParam->betAt       = Carbon::parse($row['gameDate'])->timezone($tz)->format('d-m-Y H:i:s');
        $callBackParam->reportAt    = Carbon::parse($row['lastModifyTime'])->timezone($tz)->format('d-m-Y H:i:s');
        $callBackParam->content     = json_encode($row);
        $callBackParam->round       = $row['mtype'];
        $callBackParam->table       = $row['gType'];
        $callBackParam->ip          = $row['playerIp'];
        $callBackParam->status      = Report::STATUS_COMPLETED;

        if (array_key_exists('validBet', $row)) {
            $callBackParam->validAmount = $row['validBet'];
        }

        return $callBackParam;
    }

    private function doSendProcess($params)
    {
        $apiUrl      = $this->config->apiUrl.'/apiRequest.do';
        $encryptData = $this->encrypt($params);
        $data        = ['dc'=>$this->config->dc, 'x'=>$encryptData];

        $ch          = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function encrypt($str)
    {
        $key       = $this->config->aesKey;
        $iv        = $this->config->aesIv;
        $str       = $this->padString($str);
        $encrypted = openssl_encrypt($str, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $data      = base64_encode($encrypted);
        $data      = str_replace(['+', '/', '='], ['-', '_', ''], $data);

        return $data;
    }

    public function decrypt($code)
    {
        $code      = str_replace(['-', '_'], ['+', '/'], $code);
        $code      = base64_decode($code);
        $key       = $this->config->aesKey;
        $iv        = $this->config->aesIv;
        $decrypted = openssl_decrypt($code, 'AES-128-CBC', $key, OPENSSL_NO_PADDING, $iv);

        return utf8_encode(trim($decrypted));
    }

    private function padString($source)
    {
        $paddingChar = ' ';
        $size        = 16;
        $x           = strlen($source) % $size;
        $padLength   = $size - $x;
        for ($i = 0; $i < $padLength; $i++) {
            $source .= $paddingChar;
        }

        return $source;
    }

    // 轉換成 JDB 查詢報表用的日期 GMT-4
    private function reportDateFormat($date, $sub = 0)
    {
        if ($sub != 0) {
            $date = Carbon::parse($date)->subMinutes(4)->timezone('America/New_York')->format('d-m-Y H:i:00');
        } else {
            $date = Carbon::parse($date)->timezone('America/New_York')->format('d-m-Y H:i:00');
        }

        return $date;
    }

    // 更新當下系統時間
    private function updateTs()
    {
        // GMT-4
        // $date     = Carbon::parse(now())->timezone('America/New_York')->toDateTimeString();
        // 文件上說是使用GMT-4時區 但是 TS似乎是使用UTC+0 時區  但是報表查詢 送出的日期要轉成GMT-4
        $date     = Carbon::parse(now(), 'UTC')->toDateTimeString();
        $this->ts = (int) round(Carbon::parse($date)->format('Uu') / 1000);
    }
}
