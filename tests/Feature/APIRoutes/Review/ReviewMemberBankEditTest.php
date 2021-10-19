<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Review\ReviewKeyMemberBankEditService;
use App\Models\Review\ReviewMemberBank;

class ReviewMemberBankEditTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_APPROVE = '/api/review/member-bank-edit/approve';
    const API_DISAPPROVE = '/api/review/member-bank-edit/disapprove';
    const API_LIST = '/api/review/member-bank-edit/list';
    const API_STEP_LOG = '/api/review/member-bank-edit/step-log/all';

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
            'sorts' => ['...']
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(500);
    }

    public function testSuccess()
    {
        # login
        $this->post('/api/public/login', ['account' => 'admin', 'password' => 'admin']);

        # approve
        $reviewMemberBankEdit = new ReviewMemberBank();
        $reviewMemberBankEdit->member_id = 5;
        $reviewMemberBankEdit->action = 'add';
        $reviewMemberBankEdit->name = 'xxx';
        $reviewMemberBankEdit->account = 'xxx';
        $reviewMemberBankEdit->bank_name = 'xxx';
        $reviewMemberBankEdit->branch_name = 'xxx';
        $reviewMemberBankEdit->review_step_count = 0;
        $reviewMemberBankEdit->review_step = 0;
        $reviewMemberBankEdit->review_role_id = 0;
        $reviewMemberBankEdit->status = 'pending';
        $reviewMemberBankEdit->saveOrError();
        $review = new ReviewKeyMemberBankEditService($reviewMemberBankEdit);
        $review->commit();

        $data = [
            'id' => $reviewMemberBankEdit->id,
            'remark' => 'xxx'
        ];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(200);

        # disapprove
        $reviewMemberBankEdit = new ReviewMemberBank();
        $reviewMemberBankEdit->member_id = 5;
        $reviewMemberBankEdit->action = 'add';
        $reviewMemberBankEdit->name = 'xxx';
        $reviewMemberBankEdit->account = 'xxx';
        $reviewMemberBankEdit->bank_name = 'xxx';
        $reviewMemberBankEdit->branch_name = 'xxx';
        $reviewMemberBankEdit->review_step_count = 0;
        $reviewMemberBankEdit->review_step = 0;
        $reviewMemberBankEdit->review_role_id = 0;
        $reviewMemberBankEdit->status = 'pending';
        $reviewMemberBankEdit->saveOrError();
        $review = new ReviewKeyMemberBankEditService($reviewMemberBankEdit);
        $review->commit();

        $data = [
            'id' => $reviewMemberBankEdit->id,
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
        $data = [];
        $this->call('GET', static::API_STEP_LOG, $data)
            ->assertStatus(200);
    }
}
