<?php

namespace GameProvider\Operator\Multi\Api;

use GameProvider\Operator\BaseApi;
use GameProvider\Operator\Feedback\BalanceFeedback;
use GameProvider\Operator\Feedback\LaunchGameFeedback;
use GameProvider\Operator\Feedback\MemberFeedback;
use GameProvider\Operator\Feedback\TransferFeedback;
use GameProvider\Operator\Multi\BaseMultiWalletInterface;
use GameProvider\Operator\Multi\Config\PNGConfigConstract;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\TransferParameter;
use Illuminate\Support\Carbon;

class PNG extends BaseApi implements BaseMultiWalletInterface
{
    const PLATFORM_KEY = 'PNG';

    const REPORT_TIMEZONE = 'UTC';

    protected $config;

    public function __construct(array $config)
    {
        $this->config = new PNGConfigConstract();

        $this->config->wsdlUrl  = $config['wsdlUrl'];
        $this->config->apiUrl   = $config['apiUrl'];
        $this->config->gameUrl  = $config['gameUrl'];
        $this->config->username = $config['username'];
        $this->config->password = $config['password'];
        $this->config->currency = $config['currency'];
        $this->config->language = $config['language'];
        $this->config->country  = $config['country'];
        $this->config->brandId  = $config['brandId'];
        $this->config->pid      = $config['pid'];
        $ips                            = $config['allowLiveFeedIps'] ?? false;
        $this->config->allowLiveFeedIps = $ips !== false ? explode(';', $ips) : null;
    }

    public function getGameList()
    {
    }

    public function createMember(MemberParameter $member)
    {
        $params = [
            'ExternalUserId' => $member->playerId,
            'Username'       => $member->playerId,
            'Nickname'       => $member->playerId,
            'Currency'       => $this->config->currency,
            'Country'        => $this->config->country,
            'Birthdate'      => '1980-01-02',
            'Registration'   => date('Y-m-d'),
            'BrandId'        => $this->config->brandId,
            'Language'       => $this->config->language,
            'IP'             => request()->ip(),
            'Locked'         => false,
            'Gender'         => null,
        ];

        $this->doSendProcess('RegisterUser', ['UserInfo' => $params]);

        return new MemberFeedback();
    }

    public function deposit(TransferParameter $transfer)
    {
        $params = [
            'ExternalUserId'         => $transfer->member->playerId,
            'Amount'                => $transfer->amount,
            'Currency'              => $this->config->currency,
            'ExternalTransactionId'  => $this->GUID(),
            // 'Game'                   => null,
            // 'ExternalGameSessionId'  => null,
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess('CreditAccount', $params);

        $transferFeedback->balance      = $result->UserAccount->Real;
        $transferFeedback->uid          = $result->UserAccount->TransactionId;
        $transferFeedback->remote_payno = $result->UserAccount->TransactionId;

        return $transferFeedback;
    }

    public function withdraw(TransferParameter $transfer)
    {
        $params = [
            'ExternalUserId'         => $transfer->member->playerId,
            'Amount'                => $transfer->amount,
            'Currency'              => $this->config->currency,
            'ExternalTransactionId'  => $this->GUID(),
        ];

        $transferFeedback = new TransferFeedback();

        $result = $this->doSendProcess('DebitAccount', $params);

        $transferFeedback->balance      = $result->UserAccount->Real;
        $transferFeedback->uid          = $result->UserAccount->TransactionId;
        $transferFeedback->remote_payno = $result->UserAccount->TransactionId;

        return $transferFeedback;
    }

    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $params = [
            'ExternalUserId' => md5($launchGameParams->member->playerId),
        ];

        $launchGameFeedback = new LaunchGameFeedback();

        $result = $this->doSendProcess('GetTicket', $params);

        $launchGameFeedback->gameUrl       = $result->Ticket;
        $launchGameFeedback->mobileGameUrl = $result->Ticket;

        return $launchGameFeedback;
    }

    public function getBalance(MemberParameter $member)
    {
        $params = [
            'ExternalUserId' => $member->playerId,
        ];

        $balanceFeedback = new BalanceFeedback();

        $result = $this->doSendProcess('Balance', $params);

        $balanceFeedback->balance = $result->UserBalance->Real;

        return $balanceFeedback;
    }

    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
    }

    /**
     * 注意這個API是用soap玩的.
     *
     * @param string $method
     * @param array $params
     * @return object
     */
    private function doSendProcess(string $method, array $params)
    {
        $url = $this->config->wsdlUrl;

        $options = [
            'location' => $this->config->apiUrl,
            'login'    => $this->config->username,
            'password' => $this->config->password,
        ];

        $client   = new \SoapClient($url, $options);
        $response = $client->{$method}($params);

        return $response;
    }

    public function isLiveFeedIpAllowed(string $ip)
    {
        if ($this->config->allowLiveFeedIps === null) {
            return true;
        }

        return in_array($ip, $this->config->allowLiveFeedIps);
    }

    public static function toLocalTime(string $dt)
    {
        return Carbon::parse($dt, self::REPORT_TIMEZONE)
        ->timezone(config('app.timezone'))
        ->toDateTimeString();
    }

    /**
     * png 手機版的遊戲編號會是100開頭, 去除後可以得到正確的編號
     * 目前尚無其他100開頭的遊戲, 暫時先這樣確保正確.
     */
    public static function excludeMobileCode($code)
    {
        return intval(preg_replace('/^100/', '', $code));   // 怕有zerofill，直接轉成整數
    }
}
