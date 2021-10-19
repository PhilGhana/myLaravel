<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Review\ReviewKeyAgentDepositBankService;
use App\Models\Review\ReviewAgentDepositBank;

class ReviewAgentDepositTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_APPROVE = '/api/review/agent-deposit/approve';
    const API_DISAPPROVE = '/api/review/agent-deposit/disapprove';
    const API_LIST = '/api/review/agent-deposit/list';
    const API_STEP_LOG = '/api/review/member-deposit/step-log/all';

    public function testError()
    {
        # approve
        $data = [];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'remark' => 'xxx',
            'fee' => 'xxx'
        ];
        $this->post(static::API_APPROVE, $data)
        ->assertStatus(400)
        ->assertExactJson([
            'errors' => [
                'id' => ['The selected id is invalid.'],
                'fee' => ['The fee must be an integer.']
            ],
            'message' => 'fail'
        ]);

        # disapprove
        $data = [];
        $this->post(static::API_DISAPPROVE, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'reason' => ['The reason field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'reason' => 'xxx',
            'remark' => 'xxx',
        ];
        $this->post(static::API_DISAPPROVE, $data)
        ->assertStatus(400)
        ->assertExactJson([
            'errors' => [
                'id' => ['The selected id is invalid.'],
            ],
            'message' => 'fail'
        ]);

        # list
        $data = [
            'status' => 'xxx',
            'page' => 'xxx',
            'perPage' => 'xxx'
        ];
        $this->call('GET', static::API_LIST, $data)
        ->assertStatus(400)
        ->assertExactJson([
            'errors' => [
                'status' => ['The selected status is invalid.'],
                'page' => ['The page must be an integer.'],
                'perPage' => ['The per page must be an integer.']
            ],
            'message' => 'fail'
        ]);
        $data = [
            'sorts' => ['...']
        ];
        $this->call('GET', static::API_LIST, $data)
        ->assertStatus(500);
    }

    public function testSuccess()
    {
        # add test info
        $this->post('/api/public/login', ['account' => 'admin', 'password' => 'admin']);
        $agentDeposit = new ReviewAgentDepositBank();
        $agentDeposit->agent_id = 5;
        $agentDeposit->money = 1;
        $agentDeposit->fee = 0;
        $agentDeposit->bank_id = 3;
        $agentDeposit->payee_name = 'xxx';
        $agentDeposit->payee_account = 'xxx';
        $agentDeposit->payee_bank_name = 'xxx';
        $agentDeposit->payee_branch_name = 'xxx';
        $agentDeposit->review_step_count = 0;
        $agentDeposit->review_step = 0;
        $agentDeposit->review_role_id = 0;
        $agentDeposit->status = 'pending';
        $agentDeposit->saveOrError();

        $review = new ReviewKeyAgentDepositBankService($agentDeposit);
        $review->commit();

        # approve
        $data = [
            'id' => $agentDeposit->id,
        ];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(200);
        $data = [
            'id' => $agentDeposit->id,
        ];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(200);

        # disapprove
        $agentDeposit = new ReviewAgentDepositBank();
        $agentDeposit->agent_id = user()->model()->id;
        $agentDeposit->money = 1;
        $agentDeposit->fee = 0;
        $agentDeposit->bank_id = 3;
        $agentDeposit->payee_name = 'xxx';
        $agentDeposit->payee_account = 'xxx';
        $agentDeposit->payee_bank_name = 'xxx';
        $agentDeposit->payee_branch_name = 'xxx';
        $agentDeposit->review_step_count = 0;
        $agentDeposit->review_step = 0;
        $agentDeposit->review_role_id = 0;
        $agentDeposit->status = 'pending';
        $agentDeposit->saveOrError();

        $review = new ReviewKeyAgentDepositBankService($agentDeposit);
        $review->commit();

        $data = [
            'id' => $agentDeposit->id,
            'reason' => 'xxx'
        ];
        $this->post(static::API_DISAPPROVE, $data)
            ->assertStatus(200);

        # list
        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
        $data = [
            'status' => 'review',
            'page' => 1,
            'perPage' => 1,
            'sorts' => ['money,desc']
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # step_log
        $data = [];
        $this->call('GET', static::API_STEP_LOG, $data)
            ->assertStatus(200);
    }
}