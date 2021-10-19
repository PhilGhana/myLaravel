<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Member;

class AgentTradeTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_MONEY_TO_SETTLEMENT = '/api/agent-trade/money-to-settlement';
    const API_SETTLEMENT_TO_MONEY = '/api/agent-trade/settlement-to-money';
    const API_TRANSFER_AGENT = '/api/agent-trade/transfer-agent';
    const API_GIVE_MONEY = '/api/agent-trade/give-money';
    const API_TAKE_BACK = '/api/agent-trade/take-back';
    const API_AGENT_OPTIONS = '/api/agent-trade/agent-options';
    const API_MEMBER_OPTIONS = '/api/agent-trade/member-options';

    public function testError()
    {
        # login
        $this->post('/api/public/login', [
            'account' => 'larry',
            'password' => 'ivan'
        ]);

        # money-to-settlement
        $data = [];
        $this->post(static::API_MONEY_TO_SETTLEMENT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => '金額不正確, 必需為正整數'
            ]);
        $data = ['money' => 'xxx'];
        $this->post(static::API_MONEY_TO_SETTLEMENT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => '金額不正確, 必需為正整數'
            ]);

        # settlement-to-money
        $data = [];
        $this->post(static::API_SETTLEMENT_TO_MONEY, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => '金額不正確, 必需為正整數'
            ]);
        $data = ['money' => 'xxx'];
        $this->post(static::API_SETTLEMENT_TO_MONEY, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => '金額不正確, 必需為正整數'
            ]);

        # transfer-agent
        $data = [];
        $this->post(static::API_TRANSFER_AGENT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'agentId' => ['The agent id field is required.'],
                    'money' => ['The money field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'agentId' => 'xxx',
            'money' => 'xxx'
        ];
        $this->post(static::API_TRANSFER_AGENT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'agentId' => ['The selected agent id is invalid.'],
                    'money' => ['The money must be a number.'],
                ],
                'message' => 'fail'
            ]);

        # give-money
        $data = [];
        $this->post(static::API_GIVE_MONEY, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'memberId' => ['The member id field is required.'],
                    'money' => ['The money field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'memberId' => 'xxx',
            'money' => 'xxx'
        ];
        $this->post(static::API_GIVE_MONEY, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'memberId' => ['The selected member id is invalid.'],
                    'money' => ['The money must be a number.'],
                ],
                'message' => 'fail'
            ]);

        # take-back
        $data = [];
        $this->post(static::API_TAKE_BACK, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'memberId' => ['The member id field is required.'],
                    'money' => ['The money field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'memberId' => 'xxx',
            'money' => 'xxx'
        ];
        $this->post(static::API_TAKE_BACK, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'memberId' => ['The selected member id is invalid.'],
                    'money' => ['The money must be a number.']
                ],
                'message' => 'fail'
            ]);
    }

    public function testSuccess()
    {
        # login
        $this->post('/api/public/login', [
            'account' => 'larry',
            'password' => 'ivan'
        ]);
        Member::where('id', 2)->update([
            'enabled' => 1,
            'locked' => 0,
        ]);

        # money-to-settlement
        $data = ['money' => 1];
        $this->post(static::API_MONEY_TO_SETTLEMENT, $data)
            ->assertStatus(200);

        # settlement-to-money
        $data = ['money' => 1];
        $this->post(static::API_SETTLEMENT_TO_MONEY, $data)
            ->assertStatus(200);

        # transfer-agent
        $data = [
            'agentId' => 6,
            'money' => 1
        ];
        $this->post(static::API_TRANSFER_AGENT, $data)
            ->assertStatus(200);

        # give-money
        $data = [
            'memberId' => 2,
            'money' => 1
        ];
        $this->post(static::API_GIVE_MONEY, $data)
            ->assertStatus(200);

        # take-back
        $data = [
            'memberId' => 2,
            'money' => 1
        ];
        $this->post(static::API_TAKE_BACK, $data)
            ->assertStatus(200);

        # agent-options
        $data = [];
        $this->call('GET', static::API_AGENT_OPTIONS, $data)
            ->assertStatus(200);

        # member-options
        $data = [];
        $this->call('GET', static::API_MEMBER_OPTIONS, $data)
            ->assertStatus(200);
    }
}
