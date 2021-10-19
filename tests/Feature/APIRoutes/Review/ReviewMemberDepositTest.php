<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Bank;
use App\Models\Member;
use App\Services\Review\ReviewKeyMemberDepositService;
use App\Models\Review\ReviewMemberDepositBank;

class ReviewMemberDepositTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_APPROVE = '/api/review/member-deposit/approve';
    const API_DISAPPROVE = '/api/review/member-deposit/disapprove';
    const API_LIST = '/api/review/member-deposit/list';
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
            'remark' => 'xxx'
        ];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.']
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
            'remark' => 'xxx'
        ];
        $this->post(static::API_DISAPPROVE, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.']
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
            'sorts' => ['xxx']
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(500);
    }

    public function testSuccess()
    {
        # login
        $this->post('/api/public/login', ['account' => 'admin', 'password' => 'admin']);

        # approve
        $bank = Bank::findOrError(1);
        $member = Member::findOrError(99);
        $reviewMemberDepositBank = new ReviewMemberDepositBank();
        $reviewMemberDepositBank->member_id = $member->id;
        $reviewMemberDepositBank->money = 1;
        $reviewMemberDepositBank->fee = 0;
        $reviewMemberDepositBank->bank_id = $bank->id;
        $reviewMemberDepositBank->payer_name = $member->name;
        $reviewMemberDepositBank->payee_name = $bank->name;
        $reviewMemberDepositBank->payee_account = $bank->account;
        $reviewMemberDepositBank->payee_bank_name = $bank->bank_name;
        $reviewMemberDepositBank->payee_branch_name = $bank->branch_name;
        $reviewMemberDepositBank->review_step_count = 0;
        $reviewMemberDepositBank->review_step = 0;
        $reviewMemberDepositBank->review_role_id = 0;
        $reviewMemberDepositBank->status = 'pending';
        $reviewMemberDepositBank->saveOrError();
        $review = new ReviewKeyMemberDepositService($reviewMemberDepositBank);
        $review->commit();

        $data = [
            'id' => $reviewMemberDepositBank->id,
            'remark' => 'xxx'
        ];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(200);

        # disapprove
        $bank = Bank::findOrError(1);
        $member = Member::findOrError(99);
        $reviewMemberDepositBank = new ReviewMemberDepositBank();
        $reviewMemberDepositBank->member_id = $member->id;
        $reviewMemberDepositBank->money = 1;
        $reviewMemberDepositBank->fee = 0;
        $reviewMemberDepositBank->bank_id = $bank->id;
        $reviewMemberDepositBank->payer_name = $member->name;
        $reviewMemberDepositBank->payee_name = $bank->name;
        $reviewMemberDepositBank->payee_account = $bank->account;
        $reviewMemberDepositBank->payee_bank_name = $bank->bank_name;
        $reviewMemberDepositBank->payee_branch_name = $bank->branch_name;
        $reviewMemberDepositBank->review_step_count = 0;
        $reviewMemberDepositBank->review_step = 0;
        $reviewMemberDepositBank->review_role_id = 0;
        $reviewMemberDepositBank->status = 'pending';
        $reviewMemberDepositBank->saveOrError();
        $review = new ReviewKeyMemberDepositService($reviewMemberDepositBank);
        $review->commit();

        $data = [
            'id' => $reviewMemberDepositBank->id,
            'reason' => 'xxx',
            'remark' => 'xxx'
        ];
        $this->post(static::API_DISAPPROVE, $data)
            ->assertStatus(200);

        # list
        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
        $data = [
            'status' => 'all',
            'page' => 1,
            'perPage' => 1,
            'sorts' => ['status,desc']
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # step-log
        $this->call('GET', static::API_STEP_LOG, $data)
            ->assertStatus(200);
    }
}