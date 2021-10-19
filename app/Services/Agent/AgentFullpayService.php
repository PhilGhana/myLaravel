<?php

namespace App\Services\Agent;

use App\Exceptions\FailException;
use App\Models\Agent;
use App\Models\AgentFullpayChannel as AgentFullpay;
use App\Models\Franchisee;
use App\Models\Fullpay;
use App\Models\MemberFullpayChannel as MemberFullpay;

class AgentFullpayService
{
    /**
     * 組織成員
     *
     * @var Agent
     */
    private $agent;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    /**
     * 取得上一層通道設定.
     *
     * @param Agent agent_id
     *
     * @return void
     */
    public function getParentChannel()
    {
        $agent = $this->agent;
        $pid   = [];
        // 將組織線上層 依序抓出來
        for ($i=$agent->level - 1; $i >= 1; $i--) {
            $pid[] += $agent->{"lv{$i}"};
        }

        return AgentFullpay::whereIn('agent_id', $pid)->orderBy('level', 'desc')->first();
    }

    /**
     * 取得自己的通道設定 如果沒有 逐步往上一層抓通道.
     *
     * @param Agent agent_id
     *
     * @return void
     */
    public function getMyChannelOrParentChannel()
    {
        $agent         = $this->agent;
        $agentFullpay  = AgentFullpay::find($agent->id);
        // 假如找不到自己的AgentFullpay資料 Franchisee.member_fullpay_mode模式true 才往上層找
        if (! $agentFullpay || $agentFullpay->channel == null) {
            $config  = Franchisee::findOrError($agent->franchisee_id);
            if ($config->member_fullpay_mode && $agent->level > 1) {
                return $this->getParentChannel();
            }
        }

        return $agentFullpay;
    }

    /**
     * 非公司成員 取得自己的所有通道id.
     *
     * @param Agent agent_id
     *
     * @return void
     */
    public function getMyAuthorityChannelId()
    {
        $agent         = $this->agent;
        $bank_group_id = null;
        $agentFullpay  = $this->getMyChannelOrParentChannel();

        if (! $agentFullpay) {
            return [];
        }

        return json_decode($agentFullpay->channel, true);
    }

    /**
     * 檢查 非公司成員 修改底下的 agent 是否是同組織線.
     *
     * @param Agent agent_id
     *
     * @return void
     */
    public function checkModifyAuthority()
    {
        $agent         = $this->agent;
        $user          = user()->model();
        $childrenHas   = false;
        // 檢查登入者是否跟 $agent 同線組織
        $userId = $user->id;
        // 子帳號 需使用主帳號身分
        if (($user->extend_id) && ($agent)) {
            $userId = $user->extend_id;
            if (($agent) && ($agent->id !== $userId)) {
                $childrenHas = ! $user->extend->childrenHas($agent);
            }
        } elseif ((! $user->extend_id) && ($agent)) {
            if (($agent) && ($agent->id !== $userId)) {
                $childrenHas = ! $user->childrenHas($agent);
            }
        }

        if ($agent && (! $user->isCompany() && $userId !== $agent->id && $childrenHas)) {
            throw new FailException(__('agent.not-organization'));
        }
    }

    /**
     * 取得會員自己的所有通道id.
     *
     * @param int member_id
     *
     * @return array
     */
    public function getMemberAuthorityChannelId(int $member_id)
    {
        $agent          = $this->agent;
        $fullpay        = MemberFullpay::find($member_id);
        // 會員找不到設定 找代理 ,MemberFullpay的bank_group_id=0當作他沒有找到設定
        if (! $fullpay || $fullpay->bank_group_id == 0) {
            $fullpay   = AgentFullpay::find($agent->id);
            // 代理找不到 依設定邏輯找
            if (! $fullpay) {
                $config         = Franchisee::findOrError($agent->franchisee_id);
                if ($config->member_fullpay_mode) {
                    $fullpay = $this->getParentChannel();
                }
            }
        }

        if (! $fullpay) {
            return [];
        }
        $channel = null;
        if ($fullpay->channel) {
            $channel = json_decode($fullpay->channel, true);
        }

        return [
                'channel'           => $channel,
                'bank_group_id'     => $fullpay->bank_group_id,
                'is_preset'         => $fullpay->is_preset,
             ];
    }
}