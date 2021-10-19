<?php

namespace App\Http\Controllers\Agent;

use App\Exceptions\ErrorException;
use App\Exceptions\FailException;
use App\Models\Agent;
use App\Models\AgentFullpayChannel as AgentFullpay;
use App\Models\BankGroup;
use App\Models\Franchisee;
use App\Models\Fullpay;
use App\Models\Member;
use App\Models\MemberFullpayChannel as MemberFullpay;
use App\Services\Agent\AgentFullpayService;
use App\Validators\AgentFullpay\AgentFullpayValidator;
use DB;
use Illuminate\Routing\Controller as BaseController;

class AgentFullpayController extends BaseController
{
    /**
     * 取得代理 可用的通道.
     * @param int id
     *
     * @return Fullpay
     */
    public function getOption()
    {
        $data   = request()->all();
        $user   = user()->model();
        $option = [];
        if ($data['level'] == 6) {
            AgentFullpayValidator::checkMember($data);
            $member      = Member::findOrError($data['id']);
            $data['id']  = $member->alv5;
        }
        AgentFullpayValidator::checkAgent($data);
        $agent = Agent::findOrError($data['id']);

        // 加盟主自己抓取群組通道(Fullpay)是依照自己的加盟商ID全抓(跟公司帳號一樣)
        if ($user->isCompany() || (! $user->isCompany() && $user->level == 1)) {
            if ($data['bank_group_id'] == '0' || $data['bank_group_id'] == 0) {
                throw new FailException(__('agent.not-found-bank-group-id'));
            }
            $option = $this->getOptionBybankGroupId($agent->franchisee_id, $data);
        } else {
            $service      = new AgentFullpayService($user);
            $agentFullpay = $service->getMyChannelOrParentChannel();
            if ($agentFullpay) {
                $option = Fullpay::whereIn('id', json_decode($agentFullpay->channel, true))->where('payType', Fullpay::PAYTYPE_PAYMENT)->where('status', 1)->get();
            }
        }

        return apiResponse()->data($option);
    }

    /**
     * 依加盟商抓取可用的通道.
     *
     * @param int franchisee_id
     * @param array data
     *
     * @return Fullpay
     */
    public function getOptionBybankGroupId(int $franchisee_id, array $data)
    {
        $option = Fullpay::leftjoin('bank_group', 'bank_group.fullpay', 'group_name')
            ->where('payType', Fullpay::PAYTYPE_PAYMENT)
            ->where('bank_group.franchisee_id', $franchisee_id)
            ->where('fullpay.status', 1)
            ->where('bank_group.enabled', 1);

        if ($data['bank_group_id']) {
            $option->where('bank_group.id', $data['bank_group_id']);
        }

        return $option->select([
                'fullpay.id',
                'fullpay.name',
             ])
            ->get();
    }

    /**
     * 寫入通道設定.
     *
     * @param int agent_id
     * @param array channel (fullpay.id)
     * @param array bank_group_id (bank_group.id)
     *
     * @return void
     */
    public function modify()
    {
        $inputs = request()->all();
        if ($inputs['level'] == 6) {
            $this->modifyMember();
        } else {
            $this->modifyAgent();
        }
    }

    /**
     * 寫入代理通道設定.
     *
     * @param int agent_id
     * @param array channel (fullpay.id)
     * @param array bank_group_id (bank_group.id)
     *
     * @return void
     */
    public function modifyAgent()
    {
        $inputs            = request()->all();
        $inputs['channel'] = $inputs['channel'] ?? [];
        $user              = user()->model();
        AgentFullpayValidator::checkModify($inputs);

        $agent        = Agent::findOrError($inputs['id']);

        // 子帳號改抓父帳號
        if ($user->extend_id) {
            $user = Agent::findOrError($user->extend_id);
        }

        if (! $user->isCompany()) {
            // 檢查 修改的資料 是否是自己的 組織成員
            $checkModifyAuthority = new AgentFullpayService($agent);
            $checkModifyAuthority->checkModifyAuthority();

            // 加盟主跟公司帳號一樣雍有所有通道的權限
            $changeChannel = false;
            if ($user->level != 1) {
                $service       = new AgentFullpayService($user);
                $agentFullpay  = $service->getMyAuthorityChannelId();
                $changeChannel = (! empty(array_diff($inputs['channel'], $agentFullpay)));
            }
            // 檢查是否存在非法通道
            if ((isset($inputs['channel'])) && $changeChannel) {
                throw new FailException(__('agent.no-competence-channel'));
            }
        }

        if (isset($inputs['channel'])) {
            $inputs['channel'] = array_unique($inputs['channel']);

            if (count($inputs['channel']) == 0) {
                $inputs['channel'] = json_encode([]);
            } else {
                $inputs['channel'] = json_encode($inputs['channel']);
            }
        } else {
            //當通道channel 為空值 表示該成員啥通道都不能看
            $inputs['channel'] = json_encode([]);
        }

        try {
            DB::beginTransaction();
            // 清空下層
            $this->clearSubAgentChannel($agent, $inputs);
            if ($inputs['bank_group_id'] > 0) {
                AgentFullpay::updateOrCreate(
                    [
                        'agent_id' => $inputs['id'],
                    ],
                    [
                        'channel'             => $inputs['channel'],
                        'bank_group_id'       => $inputs['bank_group_id'],
                        'level'               => $agent->level,
                    ]
                );
            } else {
                AgentFullpay::where('agent_id', $inputs['id'])->delete();
            }

            DB::commit();
        } catch (ErrorException $e) {
            DB::rollBack();
            throw new FailException($e->getMessage());
        }
    }

    /**
     * 寫入會員通道設定.
     *
     * @param int agent_id
     * @param array channel (fullpay.id)
     * @param array bank_group_id (bank_group.id)
     *
     * @return void
     */
    public function modifyMember()
    {
        $inputs            = request()->all();
        $inputs['channel'] = $inputs['channel'] ?? [];
        $user              = user()->model();
        AgentFullpayValidator::checkModifyMember($inputs);

        // 子帳號改抓父帳號
        if ($user->extend_id) {
            $user = Agent::findOrError($user->extend_id);
        }

        $member        = Member::findOrError($inputs['id']);
        $agent         = Agent::findOrError($member->alv5);
        if (! $user->isCompany()) {
            // 檢查 修改的資料 是否是自己的 組織成員
            $checkModifyAuthority = new AgentFullpayService($agent);
            $checkModifyAuthority->checkModifyAuthority();

            // 加盟主跟公司帳號一樣雍有所有通道的權限
            $changeChannel = false;
            if ($user->level != 1) {
                $service       = new AgentFullpayService($user);
                $agentFullpay  = $service->getMyAuthorityChannelId();
                $changeChannel = (! empty(array_diff($inputs['channel'], $agentFullpay)));
            }
            // 檢查是否存在非法通道
            if ((isset($inputs['channel'])) && $changeChannel) {
                throw new FailException(__('agent.no-competence-channel'));
            }
        }
        if (isset($inputs['channel'])) {
            $inputs['channel'] = array_unique($inputs['channel']);

            if (count($inputs['channel']) == 0) {
                $inputs['channel'] = json_encode([]);
            } else {
                $inputs['channel'] = json_encode($inputs['channel']);
            }
        } else {
            //當通道channel 為空值 表示該成員啥通道都不能看
            $inputs['channel'] = json_encode([]);
        }

        try {
            DB::beginTransaction();
            if ($inputs['bank_group_id'] > 0) {
                MemberFullpay::updateOrCreate(
                    [
                        'member_id' => $inputs['id'],
                    ],
                    [
                        'channel'             => $inputs['channel'],
                        'bank_group_id'       => $inputs['bank_group_id'],
                        'is_preset'           => false,
                        ]
                    );
            } else {
                // 如果bank_group_id=0 表示要恢復到繼承父層
                MemberFullpay::where('member_id', $inputs['id'])->delete();
            }

            DB::commit();
        } catch (ErrorException $e) {
            DB::rollBack();
            throw new FailException($e->getMessage());
        }
    }

    /**
     * 清空下層代理,被上層拔除的通道.
     *
     * @param Agent $agent
     * @param array $inputs
     *
     * @return void
     */
    public function clearSubAgentChannel(Agent $agent, array $inputs)
    {
        // level == 5 已經是最下層代理 下面沒有東西可被清空
        if ($agent->level == 5) {
            return;
        }
        $inputs['channel'] = json_decode($inputs['channel'], true);
        // 抓出代理 原本的通道設定
        $agentChannel = AgentFullpay::where('agent_id', $inputs['id'])->first();
        if (empty($agentChannel) || $agentChannel->channel == null) {
            return;
        }
        // 要拔除的通道
        $diff = array_diff(json_decode($agentChannel->channel, true), $inputs['channel']);

        // 抓出所有下層代理
        $agentArr = Agent::where("lv{$agent->level}", $inputs['id'])
                            ->select('id')
                            ->get()
                            ->toArray();
        // 抓出所有下層代理的通道設定
        $channel = AgentFullpay::whereIn('agent_id', $agentArr)->select('agent_id', 'channel')->get();

        foreach ($channel as  $key => $val) {
            if ($val->channel == null) {
                continue;
            }
            $old          = json_decode($val->channel, true);
            $newChannel   = array_diff($old, $diff);
            // 如果有異動才更新資料庫
            if (! empty(array_diff($old, $newChannel))) {
                if (count($newChannel) == 0) {
                    $newChannel = null;
                } else {
                    $newChannel = json_encode(array_values($newChannel));
                }

                AgentFullpay::where('agent_id', $val->agent_id)->update(['channel' => $newChannel]);
            }
        }
    }

    /**
     * 取得該代理的設定參數.
     *
     * @param Agent agent_id
     *
     * @return void
     */
    public function getAgentparam()
    {
        $inputs = request()->all();

        if ($inputs['level'] == 6) {
            AgentFullpayValidator::checkMember($inputs);
            $param = MemberFullpay::find($inputs['id']);
        } else {
            AgentFullpayValidator::checkAgent($inputs);
            $param = AgentFullpay::find($inputs['id']);
        }

        return apiResponse()->data($param);
    }

    /**
     * 取得該角色的的三方通道繼承哪個代理帳號.
     *
     * @param id   agent_id or member_id
     * @param level
     *
     * @return void
     */
    public function getMyFullpayInherit()
    {
        $inputs = request()->all();

        // 加盟主沒有繼承對象
        if ($inputs['level'] == 1) {
            return;
        }

        if ($inputs['level'] == 6) {
            $oneself = MemberFullpay::find($inputs['id']);
            // 該角色本身有通道 所有沒有繼承對象
            if ($oneself) {
                return;
            }
            $user        = Member::find($inputs['id']);
            $user->level = 6;
        } else {
            $oneself = AgentFullpay::find($inputs['id']);
            // 該角色本身有通道 所有沒有繼承對象
            if ($oneself) {
                return;
            }
            $user = Agent::select('lv1 as alv1', 'lv2 as alv2', 'lv3 as alv3', 'lv4 as alv4', 'franchisee_id', 'level')
                            ->find($inputs['id']);
        }

        $pid     = [];
        $config  = Franchisee::findOrError($user->franchisee_id);
        // 代理角色 如果member_fullpay_mode開關沒有啟用不會往上找
        if ($config->member_fullpay_mode) {
            // 將組織線上層 依序抓出來
            for ($i = $user->level - 1; $i >= 1; $i--) {
                $pid[] = $user->{"alv{$i}"};
            }
        }
        // 查找的對象是會員且member_fullpay_mode開關沒有啟用 只會往上找一層
        if ($inputs['level'] == 6 && ! $config->member_fullpay_mode) {
            $pid[] = $user->alv5;
        }

        // 找不到任一個父ID
        if (count($pid) == 0) {
            return;
        }
        $data = AgentFullpay::leftjoin('agent', 'agent_id', '=', 'agent.id')
                    ->whereIn('agent_id', $pid)
                    ->select('agent.name', 'agent.account')
                    ->orderBy('agent_fullpay_channel.level', 'desc')
                    ->first();

        return apiResponse()->data($data);
    }
}