<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Review\ReviewAgentCoupon;

class AgentCouponTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_COMMIT = '/api/agent-coupon/commit';
    const API_REVIEW_LIST = '/api/agent-coupon/review-list';
    const API_PLATFORM_OPTIONS = '/api/agent-coupon/platform-options';
    const API_AGENT_OPTIONS = '/api/agent-coupon/agent-options';
    const API_SUITABLE_AGENTS = '/api/agent-coupon/suitable-agents';

    public function testError()
    {
        # login
        $this->post('/api/public/login', ['account' => 'larry', 'password' => 'ivan']);

        # commit
        $data = [];
        $this->post(static::API_COMMIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'name' => ['The name field is required.'],
                    'type' => ['The type field is required.'],
                    'platformId' => ['The platform id field is required.'],
                    'suitableType' => ['The suitable type field is required.'],
                    'bonusType' => ['The bonus type field is required.'],
                    'betValidMultiple' => ['The bet valid multiple field is required.'],
                    'maxTimesDay' => ['The max times day field is required.'],
                    'maxTimesTotal' => ['The max times total field is required.'],
                    'startTime' => ['The start time field is required.'],
                    'endTime' => ['The end time field is required.'],
                    'memberRegisterStart' => ['The member register start field is required.'],
                    'memberRegisterEnd' => ['The member register end field is required.'],
                    'enabled' => ['The enabled field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'name' => 'xxx',
            'type' => 'xxx',
            'platformId' => 'xxx',
            'suitableType' => 'xxx',
            'bonusType' => 'xxx',
            'bonusPercent' => 'xxx',
            'bonusAmount' => 'xxx',
            'bonusMax' => 'xxx',
            'amountMax' => 'xxx',
            'amountMin' => 'xxx',
            'betValidMultiple' => 'xxx',
            'maxTimesDay' => 'xxx',
            'maxTimesTotal' => 'xxx',
            'startTime' => 'xxx',
            'endTime' => 'xxx',
            'memberRegisterStart' => 'xxx',
            'memberRegisterEnd' => 'xxx',
            'content' => 'xxx',
            'enabled' => 'xxx',
            'remark' => 'xxx',
        ];
        $this->post(static::API_COMMIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'amountMax' => ["The amount max must be a number."],
                    'amountMin' => ["The amount min must be a number."],
                    'betValidMultiple' => ["The bet valid multiple must be a number."],
                    'bonusAmount' => ["The bonus amount must be a number."],
                    'bonusMax' => ["The bonus max must be a number."],
                    'bonusPercent' => ["The bonus percent must be a number."],
                    'bonusType' => ["The selected bonus type is invalid."],
                    'enabled' => ["The selected enabled is invalid."],
                    'endTime' => ["The end time does not match the format Y-m-d H:i:s."],
                    'maxTimesDay' => ["The max times day must be an integer."],
                    'maxTimesTotal' => ["The max times total must be an integer."],
                    'memberRegisterEnd' => ["The member register end is not a valid date."],
                    'memberRegisterStart' => ["The member register start is not a valid date."],
                    'platformId' => ["The selected platform id is invalid."],
                    'startTime' => ["The start time does not match the format Y-m-d H:i:s."],
                    'suitableType' => ["The selected suitable type is invalid."],
                    'type' => ["The selected type is invalid."],
                ],
                'message' => 'fail'
            ]);
            $data = [
                'name' => 'xxx',
                'type' => 'xxx',
                'platformId' => 'xxx',
                'suitableType' => 'agent',
                'bonusType' => 'xxx',
                'bonusPercent' => 'xxx',
                'bonusAmount' => 'xxx',
                'bonusMax' => 'xxx',
                'amountMax' => 'xxx',
                'amountMin' => 'xxx',
                'betValidMultiple' => 'xxx',
                'maxTimesDay' => 'xxx',
                'maxTimesTotal' => 'xxx',
                'startTime' => 'xxx',
                'endTime' => 'xxx',
                'memberRegisterStart' => 'xxx',
                'memberRegisterEnd' => 'xxx',
                'content' => 'xxx',
                'enabled' => 'xxx',
                'remark' => 'xxx',
                'agents' => ['xxx']
            ];
            $this->post(static::API_COMMIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'amountMax' => ["The amount max must be a number."],
                    'amountMin' => ["The amount min must be a number."],
                    'betValidMultiple' => ["The bet valid multiple must be a number."],
                    'bonusAmount' => ["The bonus amount must be a number."],
                    'bonusMax' => ["The bonus max must be a number."],
                    'bonusPercent' => ["The bonus percent must be a number."],
                    'bonusType' => ["The selected bonus type is invalid."],
                    'enabled' => ["The selected enabled is invalid."],
                    'endTime' => ["The end time does not match the format Y-m-d H:i:s."],
                    'maxTimesDay' => ["The max times day must be an integer."],
                    'maxTimesTotal' => ["The max times total must be an integer."],
                    'memberRegisterEnd' => ["The member register end is not a valid date."],
                    'memberRegisterStart' => ["The member register start is not a valid date."],
                    'platformId' => ["The selected platform id is invalid."],
                    'startTime' => ["The start time does not match the format Y-m-d H:i:s."],
                    'type' => ["The selected type is invalid."],
                    'agents' => ['The selected agents is invalid.']
                ],
                'message' => 'fail'
            ]);

        # review-list
        $data = [
            'type' => 'xxx',
            'platformId' => 'xxx',
            'page' => 'xxx',
            'perPage' => 'xxx'
        ];
        $this->call('GET', static::API_REVIEW_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'type' => ['The selected type is invalid.'],
                    'platformId' => ['The selected platform id is invalid.'],
                    'page' => ['The page must be an integer.'],
                    'perPage' => ['The per page must be an integer.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'sorts' => [
                '...'
            ]
        ];
        $this->call('GET', static::API_REVIEW_LIST, $data)
            ->assertStatus(500);

        # suitable-agents
        $data = [];
        $this->call('GET', static::API_SUITABLE_AGENTS, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'not found'
            ]);
    }

    public function testSuccess()
    {
        # login
        $this->post('/api/public/login', ['account' => 'larry', 'password' => 'ivan']);

        # commit
        $data = [
            'name' => 'xxx',
            'type' => 'deposit',
            'platformId' => 23,
            'suitableType' => 'agent',
            'bonusType' => 'amount',
            'bonusPercent' => 1,
            'bonusAmount' => 1,
            'bonusMax' => 1,
            'amountMax' => 1,
            'amountMin' => 1,
            'betValidMultiple' => 1,
            'maxTimesDay' => 1,
            'maxTimesTotal' => 2,
            'startTime' => date('Y-m-d H:i:s'),
            'endTime' => date('Y-m-d H:i:s'),
            'memberRegisterStart' => date('Y-m-d'),
            'memberRegisterEnd' => date('Y-m-d'),
            'enabled' => 1,
            'agents' => [5, 6]
        ];
        $this->post(static::API_COMMIT, $data)
            ->assertStatus(200);

        # review-list
        $data = [
            'type' => 'transfer',
            'platformId' => 23,
            'page' => 1,
            'perPage' => 1
        ];
        $this->call('GET', static::API_REVIEW_LIST, $data)
            ->assertStatus(200);

        # platform-options
        $this->call('GET', static::API_PLATFORM_OPTIONS)
            ->assertStatus(200);

        # agent-options
        $data = ['account' => 'xxx'];
        $this->call('GET', static::API_AGENT_OPTIONS, $data)
            ->assertStatus(200);

        # suitable-agents
        $data = ['id' => ReviewAgentCoupon::max('id')];
        $this->call('GET', static::API_SUITABLE_AGENTS, $data)
            ->assertStatus(200);
    }
}