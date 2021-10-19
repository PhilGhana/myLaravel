<?php

namespace Tests\Feature\APIRoutes\AgentWithdraw;

use App\Models\AgentBank;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * 個人代理提款
 */
class AgentWithdrawTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_LOGIN = '/api/public/login';
    const API_COMMIT = '/api/agent-withdraw/commit';
    const API_AGENT_BANKS = '/api/agent-withdraw/agent-banks';
    const API_REVIEW_LIST = '/api/agent-withdraw/review-list';

    /**
     * commit
     *
     * @return void
     */
    public function testCommit()
    {
        // login

        $loginData = [
            'account' => 'c01-a',
            'password' => 'ivan'
        ];
        $this->post(static::API_LOGIN, $loginData)
            ->assertStatus(200);


        // ------------------- error -------------------

        $data = [];
        $this->post(static::API_COMMIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'money' => ['The money field is required.'],
                    'agentBankId' => ['The agent bank id field is required.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'money' => 999999999,
            'agentBankId' => 9999999999
        ];
        $this->post(static::API_COMMIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'agentBankId' => ['The selected agent bank id is invalid.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'money' => 999999,
            'agentBankId' => 0
        ];
        $this->post(static::API_COMMIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'agentBankId' => ['The selected agent bank id is invalid.']
                ],
                'message' => 'fail'
            ]);


        // ------------------- success -------------------

        $data = [
            'money' => 1,
            'agentBankId' => 5
        ];
        $this->post(static::API_COMMIT, $data)
            ->assertStatus(200);
    }

    /**
     * getAgentBanks
     */
    public function testGetAgentBank()
    {

        // login

        $loginData = [
            'account' => 'chloe',
            'password' => 'chloe'
        ];
        $this->post(static::API_LOGIN, $loginData)
            ->assertStatus(200);

        // 新增測試資料

        $agentBank = new AgentBank();
        $agentBank->agent_id = 3;
        $agentBank->name = 'testName';
        $agentBank->account = 'testAccount';
        $agentBank->bank_name = 'testBankName';
        $agentBank->enabled = 0;
        $agentBank->saveOrError();

        // ------------------- success -------------------

        $this->call('GET', static::API_AGENT_BANKS, [])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'account',
                        'bankName',
                        'branchName'
                    ]
                ]
            ]);

    }

    /**
     * getReviewList
     *
     * @return void
     */
    public function testReviewList()
    {

        // login

        $loginData = [
            'account' => 'c01-a',
            'password' => 'ivan'
        ];
        $this->post(static::API_LOGIN, $loginData)
            ->assertStatus(200);

        // ------------------- error -------------------

        $data = [
            'status' => 'xxx',
            'page' => 'aaa'
        ];
        $this->call('GET', static::API_REVIEW_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'status' => ['The selected status is invalid.'],
                    'page' => ['The page must be an integer.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'sorts' => ['...']
        ];
        $this->call('GET', static::API_REVIEW_LIST, $data)
            ->assertStatus(500);

        // ------------------- success -------------------

        // 新增測試資料

        $data = [
            'money' => 1,
            'agentBankId' => 5
        ];
        $this->post(static::API_COMMIT, $data)
            ->assertStatus(200);

        // test

        $data = [
            'status' => 'all'
        ];
        $this->call('GET', static::API_REVIEW_LIST, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'content' => [
                        [
                            'id',
                            'money',
                            'payeeName',
                            'payeeAccount',
                            'payeeBankName',
                            'payeeBranchName',
                            'status',
                            'reason',
                            'committedAt',
                            'finishedAt',
                            'createdAt'
                        ]
                    ],
                    'page',
                    'perPage',
                    'total'
                ]
            ]);

    }
}
