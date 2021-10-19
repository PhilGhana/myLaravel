<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Review\ReviewAgentDepositBank;

class AgentDepositTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/agent-deposit/bank-add';
    const API_COMMIT = '/api/agent-deposit/bank-commit';
    const API_CANCEL = '/api/agent-deposit/bank-cancel';
    const API_REVIEW_LIST = '/api/agent-deposit/review-list';
    const API_COMPANY_BANKS = '/api/agent-deposit/company-banks';

    # 09/28 新增api
    const API_THIRD_ADD = '/api/agent-deposit/third-add';
    const API_THIRD_PAYMENTS = '/api/agent-deposit/third-payments';
    const API_THIRD_PARAMS = '/api/agent-deposit/third-params';
    const API_THIRD_LOG_LIST = '/api/agent-deposit/third-log-list';

    public function testError()
    {
        # add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'bankId' => ['The bank id field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = ['bankId' => 'xxx'];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'bankId' => ['The selected bank id is invalid.']
                ],
                'message' => 'fail'
            ]);

        # commit
        $data = ['money' => -1];
        $this->post(static::API_COMMIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'money' => ['The money must be at least 0.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'money' => 'xxx',
            'remark' => 'xxx'
        ];
        $this->post(static::API_COMMIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'money' => ['The money must be a number.']
                ],
                'message' => 'fail'
            ]);

        # cancel
        $data = [];
        $this->post(static::API_CANCEL, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'not found'
            ]);

        # review-list
        $data = [
            'status' => 'xxx',
            'page' => 'xxx',
            'perPage' => 'xxx'
        ];
        $this->call('GET', static::API_REVIEW_LIST, $data)
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
            'page' => 0,
            'perPage' => -1
        ];
        $this->call('GET', static::API_REVIEW_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'page' => ['The page must be at least 1.'],
                    'perPage' => ['The per page must be at least 1.']
                ],
                'message' => 'fail'
            ]);

        # third-add
        $data = [];
        $this->post(static::API_THIRD_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'paymentId' => ['The payment id field is required.'],
                    'amount' => ['The amount field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'paymentId' => 'xxx',
            'amount' => 'xxx'
        ];
        $this->post(static::API_THIRD_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'amount' => ['The amount must be a number.']
                ],
                'message' => 'fail'
            ]);

        # third-params
        $data = [];
        $this->call('GET', static::API_THIRD_PARAMS, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'type' => ['The type field is required.'],
                ],
                'message' => 'fail'
            ]);

        #thied-log-list
        $data = [
            'startTime' => 'xxx',
            'endTime' => 'xxx',
            'status' => 'xxx',
            'page' => 'xxx',
            'perPage' => 'xxx',
        ];
        $this->call('GET', static::API_THIRD_LOG_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'startTime' => ['The start time does not match the format Y-m-d H:i:s.'],
                    'endTime' => ['The end time does not match the format Y-m-d H:i:s.'],
                    'status' => ['The selected status is invalid.'],
                    'page' => ['The page must be an integer.'],
                    'perPage' => ['The per page must be an integer.']
                ],
                'message' => 'fail'
            ]);
    }

    public function testSuccess()
    {
        # login
        $this->post('/api/public/login', ['account' => 'larry', 'password' => 'ivan']);

        # add
        $data = [
            'bankId' => 3
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        $reviewId = ReviewAgentDepositBank::max('id');

        # commit
        $data = [
            'id' => $reviewId,
            'money' => 666,
            'remark' => 'xxx'
        ];
        $this->post(static::API_COMMIT, $data)
            ->assertStatus(200);

        # cancel
        $data = ['bankId' => 3];
        $this->post(static::API_ADD, $data);
        $reviewId = ReviewAgentDepositBank::max('id');
        $data = ['id' => $reviewId];
        $this->post(static::API_CANCEL, $data)
            ->assertStatus(200);

        # review-list
        $data = [];
        $this->call('GET', static::API_REVIEW_LIST, $data)
            ->assertStatus(200);
        $data = [
            'status' => 'all',
            'page' => 2,
            'perPage' => 5
        ];
        $this->call('GET', static::API_REVIEW_LIST, $data)
            ->assertStatus(200);

        # company-banks
        $this->call('GET', static::API_COMPANY_BANKS)
            ->assertStatus(200);

        # third-add

        # third-payments

        # third-params

        # third-log-list
        $data = [];
        $this->call('GET', static::API_THIRD_LOG_LIST, $data)
            ->assertStatus(200);
        $data = [
            'startTime' => '2018-08-01 00:00:00',
            'endTime' => '2019-01-01 00:00:00',
            'status' => '1',
            'sorts' => [
                'startTime, desc'
            ],
            'page' => 1,
            'perPage' => 1
        ];
        $this->call('GET', static::API_THIRD_LOG_LIST, $data)
            ->assertStatus(200);
    }
}