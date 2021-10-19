<?php

/**
 * STG特化版本，不是一般泛用的單錢包，不要誤用
 * Author:BK
 */

namespace GameProvider\Operator\Single\Api;

use App\Models\Member;
use Exception;
use GameProvider\Operator\BaseApi;
use GameProvider\Operator\Single\BaseSingleWalletInterface;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\SyncReportParameter;
use GameProvider\Operator\Single\Config\STGConfigConstract;
use GameProvider\Operator\Params\MemberParameter;
use DB;
class STG extends BaseApi implements BaseSingleWalletInterface
{
    function __construct(array $config)
    {
        $this->config = new STGConfigConstract();

        $this->config->apiUrl       = $config['apiUrl'];
        $this->config->APIpartner   = $config['APIpartner'];
        $this->config->language     = $config['language'];
        $this->config->secret       = $config['secret'];
        $this->config->age          = $config['age'];
    }

    // public function addManageAccount(MemberParameter $member)
    // {
    //     $agent = Member::where('member.id', $member->member_id)
    //         ->join('agent', 'agent.id', '=', 'alv5')
    //         ->select(DB::RAW("agent.account as account, agent.name as name"))
    //         ->first();

    //     // $params = [
    //     //     'APIpartner'  => $this->config->APIpartner,
    //     //     'function'    => 'addManageAccount',
    //     //     'lv'          => 6,
    //     //     'mem'         => 'LV6',
    //     //     'alias'       => 'LV6',
    //     //     'age'         => $this->config->age,
    //     // ];

    //     // $result = $this->doSendProcess($params);

    //     // $params = [
    //     //     'APIpartner'  => $this->config->APIpartner,
    //     //     'function'    => 'addManageAccount',
    //     //     'lv'          => 5,
    //     //     'mem'         => 'LV5',
    //     //     'alias'       => 'LV5',
    //     //     'age'         => 'LV6',
    //     // ];

    //     // $result = $this->doSendProcess($params);

    //     // $params = [
    //     //     'APIpartner'  => $this->config->APIpartner,
    //     //     'function'    => 'addManageAccount',
    //     //     'lv'          => 4,
    //     //     'mem'         => 'LV4',
    //     //     'alias'       => 'LV4',
    //     //     'age'         => 'LV5',
    //     // ];

    //     // $result = $this->doSendProcess($params);

    //     // $params = [
    //     //     'APIpartner'  => $this->config->APIpartner,
    //     //     'function'    => 'addManageAccount',
    //     //     'lv'          => 3,
    //     //     'mem'         => 'LV3',
    //     //     'alias'       => 'LV3',
    //     //     'age'         => 'LV4',
    //     // ];

    //     // $result = $this->doSendProcess($params);

    //     $params = [
    //         'APIpartner'  => $this->config->APIpartner,
    //         'function'    => 'addManageAccount',
    //         'lv'          => 2,
    //         'mem'         => $agent->account,
    //         'alias'       => $agent->name,
    //         'age'         => 'LV3',
    //     ];

    //     $result = $this->doSendProcess($params);

    //     // if($result->err === false)
    //     // {
    //     //     throw new Exception('addManageAccount error : ' . $result->err_msg );
    //     // }
    // }
    public function addManageAccount($age, $lv, $account, $name)
    {
        $parent_acc = $age;
        if($lv == 6)
        {
            $parent_acc = $this->config->age;
        }

        $params = [
            'APIpartner'  => $this->config->APIpartner,
            'function'    => 'addManageAccount',
            'lvl'         => $lv,
            'mem'         => $account,
            'alias'       => urlencode($name),
            'age'         => $parent_acc,
        ];

        $result = $this->doSendProcess($params);

        if($result->err === false)
        {
            throw new Exception('addManageAccount error : ' . $result->err_msg . ' age:' . $parent_acc . ' lv:' . $lv . ' account:' . $account . ' name:' . $name );
        }
    }

    public function addMemberAccount(MemberParameter $member)
    {
        $agent = Member::where('member.id', $member->member_id)
            ->join('agent', 'agent.id', '=', 'alv5')
            ->select(DB::RAW("agent.account as account, agent.name as name"))
            ->first();

        $params = [
            'APIpartner'  => $this->config->APIpartner,
            'function'    => 'addMemberAccount',
            'mem'         => $member->username,
            'pwd'         => $member->password,
            'age'         => $agent->account,
        ];

        $result = $this->doSendProcess($params);

        if($result->err === false)
        {
            throw new Exception('addMemberAccount error : ' . $result->err_msg );
        }
    }

    public function launchGame(LaunchGameParameter $launchGameParams)
    {

    }

    public function syncReport(SyncReportParameter $srp, callable $callback)
    {

    }

    /**
     * @param array $params
     * @return mix
     */
    private function doSendProcess(array $params)
    {
        $fullParams = $this->doParamsEncode($params);
        $response   = $this->get($this->config->apiUrl . '?' . $fullParams, null, true);

        return $response;
    }

    /**
     * 參數加密
     *
     * @param array $params
     * @return array
     */
    public function doParamsEncode(array $params)
    {
        $params = collect($params);
        $params->put('APIpartner', $this->config->APIpartner);

        // 所有參數不包含 HASH，由A-Z soft排序
        $params = $params->sortKeys();

        $paramStr = '';
        foreach($params->toArray() as $key => $val) {

            if($paramStr !== '') {
                $paramStr .= '&';
            }

            $paramStr .= $key . '=' . $val;
        }

        // 將 secret 加至參數最後方
        // $hash = implode("&", $params->toArray());
        // $hash = md5($hash . $this->config->secret);
        // $params->put('hash', $hash);

        // return $params->all();

        // $param = implode("&", $params->toArray());
        $hash = md5($paramStr . $this->config->secret);

        return $paramStr . '&hash=' . $hash;
    }
}