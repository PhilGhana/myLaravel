<?php

namespace GameProvider\Operator\Multi\Api;

use GameProvider\Operator\BaseApi;

use GameProvider\Operator\Multi\Config\Sport365ConfigConstract;

use GameProvider\Operator\Multi\BaseMultiWalletInterface;

use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\TransferParameter;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;

use GameProvider\Exceptions\AesException;
use GameProvider\Exceptions\LoginException;
use GameProvider\Exceptions\GameListException;
use GameProvider\Exceptions\JSONException;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\CreateMemberException;
use GameProvider\Exceptions\LaunchGameException;
use GameProvider\Exceptions\SyncException;
use GameProvider\Exceptions\TransferException;

use GameProvider\Operator\Feedback\MemberFeedback;
use GameProvider\Operator\Feedback\TransferFeedback;
use GameProvider\Operator\Feedback\BalanceFeedback;
use GameProvider\Operator\Feedback\LaunchGameFeedback;

use App\Models\Report;

class Sport365 extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    protected $url;

    function __construct(array $config)
    {
        $this->config = new Sport365ConfigConstract();

        $this->config->apiUrl = $config['apiUrl'];
        $this->config->agid = $config['agid'];
        $this->config->site = $config['site'];
        $this->config->gameUrl = $config['gameUrl'];
    }

    public function getGameList(){}

    public function createMember(MemberParameter $member)
    {
        // http://big13-web.sog88.net/pub/gateway.php?cmd={"cmd":208,"parame":{"site":"cash","mem":"demo001","age":"demoag1"},"tt":"36991348812747328"}

        $cmd = [
            'cmd'    => 208,
            'parame' => [
                'site' => $this->config->site,
                'mem'  => $member->playerId,
                'age'  => $this->config->agid
            ]
        ];

        $result = $this->doSendProcess($cmd);

        if(isset($result->err) && $result->err != true)
        {
            throw new CreateMemberException(get_class($this), 'error when 365 createMember : ' . $result->err_msg);
        }

        $memberFeedback = new MemberFeedback();

        return $memberFeedback;
    }

    public function deposit(TransferParameter $transfer)
    {
        // http://big13-web.sog88.net/pub/gateway.php?cmd={"cmd":206,"parame":{"site":"cash","mem":"demo001","gold":10000,”refno”:51},"tt":"36991348812747328"}
        $cmd = [
            'cmd'    => 206,
            'parame' => [
                'site'  => $this->config->site,
                'mem'   => $transfer->member->playerId,
                'gold'  => $transfer->amount,
                'refno' => 'I' . $this->GUID()
            ]
        ];

        $result = $this->doSendProcess($cmd);

        if(isset($result->err) && $result->err != true)
        {
            throw new TransferException(get_class($this), 'error when 365 deposit : ' . $result->err_msg);
        }

        $ret = ($result->ret)[0];

        $transferFeedback               = new TransferFeedback();
        $transferFeedback->remote_payno = $ret->id;
        $transferFeedback->balance      = $ret->credit;

        return $transferFeedback;
    }

    public function withdraw(TransferParameter $transfer)
    {
        // http://big13-web.sog88.net/pub/gateway.php?cmd={"cmd":206,"parame":{"site":"cash","mem":"demo001","gold":10000,”refno”:51},"tt":"36991348812747328"}
        $cmd = [
            'cmd'    => 206,
            'parame' => [
                'site'  => $this->config->site,
                'mem'   => $transfer->member->playerId,
                'gold'  => $transfer->amount * -1,
                'refno' => 'O' . $this->GUID()
            ]
        ];

        $result = $this->doSendProcess($cmd);

        if(isset($result->err) && $result->err != true)
        {
            throw new TransferException(get_class($this), 'error when 365 deposit : ' . $result->err_msg);
        }

        $ret = ($result->ret)[0];

        $transferFeedback               = new TransferFeedback();
        $transferFeedback->remote_payno = $ret->id;
        $transferFeedback->balance      = $ret->credit;

        return $transferFeedback;
    }

    public function getBalance(MemberParameter $member)
    {
        // http://big13-web.sog88.net/pub/gateway.php?cmd={"cmd":200,"parame":{"site":"cash","mem":["demo001","demo002"]},"tt":"36991348812747328"}
        $cmd = [
            'cmd'    => 200,
            'parame' => [
                'site' => $this->config->site,
                'mem'  => [$member->playerId],
            ]
        ];

        $result = $this->doSendProcess($cmd);

        if(isset($result->err) && $result->err != true)
        {
            throw new BalanceException(get_class($this), 'error when 365 getBalance : ' . $result->err_msg);
        }

        $balanceFeedback          = new BalanceFeedback();
        $balanceFeedback->balance = ($result->ret)[0]->credit;

        return $balanceFeedback;
    }

    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        // http://big13-web.sog88.net/cmd={"cmd":209,"parame":{"site":"cash","mem":"demo001"},"tt":"36991348812747328"}
        $cmd = [
            'cmd'    => 209,
            'parame' => [
                'site' => $this->config->site,
                'mem'  => $launchGameParams->member->playerId,
            ]
        ];

        $result = $this->doSendProcess($cmd);

        if(isset($result->err) && $result->err != true)
        {
            throw new LaunchGameException(get_class($this), 'error when 365 launchGame : ' . $result->err_msg);
        }

        $launchGameFeedback                = new LaunchGameFeedback();
        $launchGameFeedback->gameUrl       = $this->config->gameUrl . '?uid=' . $result->ret;
        $launchGameFeedback->mobileGameUrl = $this->config->gameUrl . '?uid=' . $result->ret;
        $launchGameFeedback->token         = $result->ret;

        return $launchGameFeedback;
    }

    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        // http://big13-web.sog88.net/pub/gateway.php?cmd={"cmd":601,"parame":{"site":"cash","age":"age001","sdate":"2013-04-11","edate":"2013-04-11",”page”:”1”,"lang":"zh-tw"},"tt":"36991348812747328"}
        $cmd = [
            'cmd'    => 601,
            'parame' => [
                'site'  => $this->config->site,
                'age'   => $this->config->agid,
                'sdate' => $srp->startAt,
                'edate' => $srp->endAt,
                'page'  => 1,
                'lang'  => 'zh-tw'
            ]
        ];

        $result = $this->doSendProcess($cmd);

        if(isset($result->err) && $result->err != true)
        {
            if($result->err_msg == 'nodata')
            {
                return $callback([]);
            }
            throw new SyncException(get_class($this), 'error when 365 syncReport : ' . $result->err_msg);
        }

        $data = [];
        foreach($result->ret->report->{$this->config->agid} as $row)
        {
            $data[] = $this->makeSyncCallBackParameter($row);
        }
        
        return $callback($data);
    }

    private function doSendProcess($params, $method = 'get')
    {
        $url = $this->config->apiUrl . '?cmd=' . urlencode(json_encode($params));

        $this->url = $url;

        if($method == 'get')
        {
            return $this->get($url, json_encode([]));
        }

        return $this->post($url, json_encode($params));
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->betAt       = $row->adddate;
        $callBackParam->reportAt    = $row->adddate;
        $callBackParam->username    = $row->mem_alias;
        $callBackParam->gameCode    = '36588';
        $callBackParam->mid         = $row->id;
        $callBackParam->betAmount   = $row->gold;
        $callBackParam->validAmount = $row->effective_gold;
        $callBackParam->winAmount   = $row->wingold;
        $callBackParam->content     = $row->content;
        $callBackParam->winAmount   = ($row->wingold == '0.00')? 0:($row->wingold + $row->gold);
        $callBackParam->ip          = $row->orderIP;

        $status = [
            '0'  => Report::STATUS_BETTING,
            'N'  => Report::STATUS_CANCEL,
            'NC' => Report::STATUS_CANCEL,
            'W'  => Report::STATUS_COMPLETED,
            'L'  => Report::STATUS_COMPLETED,
            'LL' => Report::STATUS_COMPLETED,
            'LW' => Report::STATUS_COMPLETED,
        ];

        $callBackParam->status = $status[$row->result];
        

        return $callBackParam;
    }
}
