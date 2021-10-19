<?php

namespace GameProvider\Operator\Single\Api;

use App\Models\LogMemberWallet;
use GameProvider\Operator\BaseApi;

use GameProvider\Operator\Single\Config\SGConfigConstract;

use GameProvider\Operator\Single\BaseSingleWalletInterface;

use GameProvider\Services\SingleWalletService;

use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\TransferParameter;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Params\AuthorizeParameter;
use GameProvider\Operator\Params\BalanceParameter;

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
// use MultiWallet\Feedback\SyncCallBackFeedback;

use App\Models\Report;


class SG extends BaseApi implements BaseSingleWalletInterface
{
    protected $config;

    protected $token = null;

    function __construct(array $config)
    {
        $this->config = new SGConfigConstract();

        $this->config->apiUrl       = $config['apiUrl'];
        $this->config->merchantCode = $config['merchantCode'];
        $this->config->language     = $config['language'];
        $this->config->launchUrl    = $config['launchUrl'];
        $this->config->currency     = $config['currency'];

        // 先準備header，要求給json
        array_push($this->curlHeader, 'DataType:JSON');
    }

    /**
     * 進入遊戲
     *
     * @param LaunchGameParameter $launchGameParams
     * @return LaunchGameFeedback
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $params = [
            'acctId='   => $launchGameParams->member->playerId,
            'token='    => $launchGameParams->token,
            'language=' => $this->config->language,
            'game='     => $launchGameParams->gameId,
            'fun='      => $launchGameParams->fun,
            'minigame=' => 'false',
            'mobile='   => ($launchGameParams->device === 'PC') ? 'false':'true',
            'menumode=' => 'on'
        ];

        $feedback          = new LaunchGameFeedback();
        $feedback->gameUrl = $this->config->launchUrl . '?' . implode('&', $params);

        // 給網址
        return $feedback;
    }

    public function syncReport(SyncReportParameter $srp, callable $callback)
    {
        array_push($this->curlHeader, 'API:getBetHistory');

        $params = [
            'beginDate'    => $srp->startAt,
            'endDate'      => $srp->endAt,
            'token'        => $token,
            'pageIndex'    => 1,
            'merchantCode' => $this->config->merchantCode,
            'serialNo'     => $this->GUID()
        ];

        return $callback($this->doSyncReport($params));
    }

    private function doSyncReport($params)
    {
        $result = $this->doSendProcess($params, 'getBetHistory');

        $rows = $result->list;

        $data = [];

        foreach($rows as $row)
        {
            $data[] = $this->makeSyncCallBackParameter($row);
        }

        if($result->pageCount > $params['pageIndex'])
        {
            $params['pageIndex'] = $params['pageIndex'] + 1;
            $data = array_merge($data, $this->doSyncReport($params));
        }

        return $data;
    }

    private function makeSyncCallBackParameter($row)
    {
        $callBackParam = new SyncCallBackParameter();

        $callBackParam->mid         = $row->ticketId;
        $callBackParam->username    = $row->acctId;
        $callBackParam->betAmount   = $row->betAmount;
        $callBackParam->validAmount = $row->betAmount;
        $callBackParam->gameCode    = $row->gameCode;
        $callBackParam->winAmount   = $row->winLoss;
        $callBackParam->betAt       = $row->ticketTime;
        $callBackParam->reportAt    = $row->ticketTime;
        $callBackParam->ip          = $row->betIp;
        $callBackParam->round       = $row->roundId;
        $callBackParam->content     = $row->wagerdetail->betonname;
        $callBackParam->status      = ($row->completed) ? Report::STATUS_COMPLETED: Report::STATUS_BETTING;

        return $callBackParam;
    }

    private function doSendProcess($params, $method)
    {
        array_push($this->curlHeader, 'API:' . $method);

        $response = $this->post($this->config->apiUrl, $params);

        $result = json_decode($response);

        // 如果解不開，就直接把錯誤丟回去
        if($result === null)
        {
            throw new JSONException(get_class($this), 'error on JSON decode !', $result);
        }

        return $result;
    }

}
