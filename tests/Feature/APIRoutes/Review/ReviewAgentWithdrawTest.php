<?php

namespace Tests\Feature\APIRoutes\Review;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Review\ReviewKeyAgentWithdrawService;
use App\Models\Bank;
use App\Models\AgentWallet;
use App\Models\Review\ReviewAgentWithdraw;


/**
 * 審核代理提款
 */
class ReviewAgentWithdrawTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_LOGIN = '/api/public/login';
    const API_COMMIT_WITHDRAW = '/api/agent-withdraw/commit';
    const API_APPROVE = '/api/review/agent-withdraw/approve';
    const API_DISAPPROVE = '/api/review/agent-withdraw/disapprove';
    const API_LIST = '/api/review/agent-withdraw/list';
    const API_THIRDPLATFORMS = '/api/review/agent-withdraw/third-platforms';
    const API_BANK_OPTIONS = '/api/review/agent-withdraw/bank-options';
    const API_STEP_LOG = '/api/review/agent-withdraw/step-log/all';

    # 10/01 新增API
    const API_TRANSACTION = '/api/review/agent-withdraw/transaction';
    const API_THIRD_WITHDRAWS = '/api/review/agent-withdraw/third-withdraws';
    const API_THIRD_PARAMS = '/api/review/agent-withdraw/third-params';
    const API_THIRD_LOG = '/api/review/agent-withdraw/third-log-all';

    /**
     * approve
     *
     * @return void
     */
    public function testApprove()
    {
        // login

        $loginData = [
            'account' => 'admin',
            'password' => 'admin'
        ];

        $this->post(static::API_LOGIN, $loginData)
            ->assertStatus(200);


        // --------------------- error ---------------------

        $data = [];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.']
                ],
                'message' => 'fail'
            ]);

        // --------------------- success ---------------------

        // 新增測試資料

        $reviewAgentWithdraw = new ReviewAgentWithdraw();

        $reviewAgentWithdraw->agent_id = 5;
        $reviewAgentWithdraw->money = 1;
        $reviewAgentWithdraw->fee = 0;
        $reviewAgentWithdraw->payee_name = 'name';
        $reviewAgentWithdraw->payee_account = 'account';
        $reviewAgentWithdraw->payee_bank_name = 'bankName';
        $reviewAgentWithdraw->review_step_count = 0;
        $reviewAgentWithdraw->review_step = 0;
        $reviewAgentWithdraw->review_role_id = 0;
        $reviewAgentWithdraw->status = 'pending';
        $reviewAgentWithdraw->reason = '';
        $reviewAgentWithdraw->saveOrError();

        $review = new ReviewKeyAgentWithdrawService($reviewAgentWithdraw);

        $review->commit();

        // test

        $data = [
            'id' => $reviewAgentWithdraw->id,
        ];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(200);
    }

    /**
     * disapprove
     *
     * @return void
     */
    public function testDisapprove()
    {
        // login
        $loginData = [
            'account' => 'admin',
            'password' => 'admin'
        ];
        $this->post(static::API_LOGIN, $loginData)
            ->assertStatus(200);

        // --------------------- error ---------------------
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
            'id' => 999999,
            'reason' => 'xxx'
        ];
        $this->post(static::API_DISAPPROVE, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.']
                ],
                'message' => 'fail'
            ]);

        // --------------------- success ---------------------

        // 新增測試資料
        $reviewAgentWithdraw = new ReviewAgentWithdraw;
        $reviewAgentWithdraw->agent_id = 5;
        $reviewAgentWithdraw->money = 1;
        $reviewAgentWithdraw->fee = 0;
        $reviewAgentWithdraw->payee_name = 'name';
        $reviewAgentWithdraw->payee_account = 'account';
        $reviewAgentWithdraw->payee_bank_name = 'bankName';
        $reviewAgentWithdraw->review_step_count = 0;
        $reviewAgentWithdraw->review_step = 0;
        $reviewAgentWithdraw->review_role_id = 0;
        $reviewAgentWithdraw->status = 'pending';
        $reviewAgentWithdraw->reason = '';
        $reviewAgentWithdraw->saveOrError();

        $review = new ReviewKeyAgentWithdrawService($reviewAgentWithdraw);
        $review->commit();

        $agentWallet = AgentWallet::findOrError(5);
        $agentWallet->review_withdraw_money += 1;
        $agentWallet->saveOrError();

        // test
        $data = [
            'id' => $reviewAgentWithdraw->id,
            'reason' => 'xxxxxxxx'
        ];
        $this->post(static::API_DISAPPROVE, $data)
            ->assertStatus(200);
    }

    /**
     * list
     *
     * @return void
     */
    public function testList()
    {
        // login

        $loginData = [
            'account' => 'admin',
            'password' => 'admin'
        ];
        $this->post(static::API_LOGIN, $loginData)
            ->assertStatus(200);

        // --------------------- error ---------------------

        $data = [
            'status' => 'xxxx'
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'status' => ['The selected status is invalid.']
                ],
                'message' => 'fail'
            ]);

        // --------------------- success ---------------------

        // 新增測試資料

        $reviewAgentWithdraw = new ReviewAgentWithdraw;

        $reviewAgentWithdraw->agent_id = 5;
        $reviewAgentWithdraw->money = 1;
        $reviewAgentWithdraw->fee = 0;
        $reviewAgentWithdraw->payee_name = 'name';
        $reviewAgentWithdraw->payee_account = 'account';
        $reviewAgentWithdraw->payee_bank_name = 'bankName';
        $reviewAgentWithdraw->review_step_count = 0;
        $reviewAgentWithdraw->review_step = 0;
        $reviewAgentWithdraw->review_role_id = 0;
        $reviewAgentWithdraw->status = 'pending';
        $reviewAgentWithdraw->reason = '';
        $reviewAgentWithdraw->saveOrError();

        $review = new ReviewKeyAgentWithdrawService($reviewAgentWithdraw);

        $review->commit();

        // test

        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
    }

    /**
     * getThirdPlatforms
     *
     * @return void
     */
    public function testThirdPlatforms()
    {
        // --------------------- success ---------------------

        $this->call('GET', static::API_THIRDPLATFORMS)
            ->assertStatus(200);
    }

    /**
     * getBankOptions
     *
     * @return void
     */
    public function testBankOptions()
    {
        // --------------------- success ---------------------

        $this->call('GET', static::API_BANK_OPTIONS)
            ->assertStatus(200);
    }

    public function stepLog()
    {
        $data = [];
        $this->call('GET', static::API_STEP_LOG, $data)
            ->assertStatus(200);
    }

    public function testTransaction()
    {
        // login
        $data = [
            'account' => 'admin',
            'password' => 'admin'
        ];
        $this->post(static::API_LOGIN, $data)
            ->assertStatus(200);

        // 新增測試資料
        $reviewAgentWithdraw = new ReviewAgentWithdraw;
        $reviewAgentWithdraw->agent_id = 5;
        $reviewAgentWithdraw->money = 1;
        $reviewAgentWithdraw->fee = 0;
        $reviewAgentWithdraw->payee_name = 'name';
        $reviewAgentWithdraw->payee_account = 'account';
        $reviewAgentWithdraw->payee_bank_name = 'bankName';
        $reviewAgentWithdraw->review_step_count = 0;
        $reviewAgentWithdraw->review_step = 0;
        $reviewAgentWithdraw->review_role_id = 0;
        $reviewAgentWithdraw->status = 'pending';
        $reviewAgentWithdraw->reason = '';
        $reviewAgentWithdraw->saveOrError();

        $review = new ReviewKeyAgentWithdrawService($reviewAgentWithdraw);
        $review->commit();

        $agentWallet = AgentWallet::findOrError(5);
        $agentWallet->review_withdraw_money += 1;
        $agentWallet->saveOrError();

        // approve
        $reviewId = $reviewAgentWithdraw->id;
        $data = [
            'id' => $reviewId,
        ];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(200);
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(200);

        // type->bank

        // error
        $data = [];
        $this->post(static::API_TRANSACTION, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'type' => ['The type field is required.'],
                    'fee' => ['The fee field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'type' => 'xxx',
            'fee' => 'xxx'
        ];
        $this->post(static::API_TRANSACTION, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'type' => ['The selected type is invalid.'],
                    'fee' => ['The fee must be a number.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => $reviewId,
            'type' => 'bank',
            'fee' => 0
        ];
        $this->post(static::API_TRANSACTION, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'payerName' => ['The payer name field is required.'],
                    'payerAccount' => ['The payer account field is required.'],
                    'payerBankName' => ['The payer bank name field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => $reviewId,
            'type' => 'bank',
            'fee' => 0,
            'payerName' => 0,
            'payerAccount' => 0,
            'payerBankName' => 0,
            'payerBranchName' => 0,
            'transactionAt' => 0,
            'transactionId' => 0
        ];
        $this->post(static::API_TRANSACTION, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'payerName' => ['The payer name must be a string.'],
                    'payerAccount' => ['The payer account must be a string.'],
                    'payerBankName' => ['The payer bank name must be a string.'],
                    'payerBranchName' => ['The payer branch name must be a string.'],
                    'transactionAt' => ['The transaction at does not match the format Y-m-d H:i:s.'],
                    'transactionId' => ['The transaction id must be a string.'],
                ],
                'message' => 'fail'
            ]);

        // success
        $data = [
            'id' => $reviewId,
            'type' => 'bank',
            'fee' => 0,
            'payerName' => 'xxx',
            'payerAccount' => 'xxx',
            'payerBankName' => 'xxx',
            'payerBranchName' => 'xxx',
            'transactionAt' => date('Y-m-d H:i:s'),
            'transactionId' => 'xxx',
        ];
        $this->post(static::API_TRANSACTION, $data)
            ->assertStatus(200);
    }

    public function testThirdWithdraws()
    {
        $data = [];
        $this->call('GET', static::API_THIRD_WITHDRAWS)
            ->assertStatus(200);
    }

    public function testThirdLogAll()
    {
        $data = [];
        $this->call('GET', static::API_THIRD_LOG, $data)
            ->assertStatus(200);
        $data = [
            'id' => 'xxx'
        ];
        $this->call('GET', static::API_THIRD_LOG, $data)
            ->assertStatus(200);
        $data = [
            'id' => 5
        ];
        $this->call('GET', static::API_THIRD_LOG, $data)
            ->assertStatus(200);
    }
}
