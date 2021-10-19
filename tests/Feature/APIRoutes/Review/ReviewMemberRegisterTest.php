<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Review\ReviewKeyMemberRegisterService;
use App\Models\Review\ReviewMemberRegister;

class ReviewMemberRegisterTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_APPROVE = '/api/review/member-register/approve';
    const API_DISAPPROVE = '/api/review/member-register/disapprove';
    const API_LIST = '/api/review/member-register/list';
    const API_CLUB_OPTIONS = '/api/review/member-register/club-options';
    const API_CLUB_RANK_OPTIONS = '/api/review/member-register/club-rank-options';
    const API_STEP_LOG = '/api/review/member-register/step-log/all';

    public function testError()
    {
        # approve
        $data = [];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'clubRankId' => ['The club rank id field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'remark' => 'xxx',
            'clubRankId' => 'xxx'
        ];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'clubRankId' => ['The selected club rank id is invalid.']
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
                    'perPage' => ['The per page must be an integer.'],
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
        $review = new ReviewMemberRegister();
        $review->account = 'xxx';
        $review->password = 'xxx';
        $review->name = 'xxx';
        $review->invitation_code = 'Md56ba7d';
        $review->gender = 'NA';
        $review->phone = 'xxx';
        $review->email = 'xxx';
        $review->qq = 'xxx';
        $review->wechat = 'xxx';
        $review->weibo = 'xxx';
        $review->review_step_count = 0;
        $review->review_step = 0;
        $review->review_role_id = 0;
        $review->status = 'pending';
        $review->saveOrError();
        $reviewMember = new ReviewKeyMemberRegisterService($review);
        $reviewMember->commit();

        $data = [
            'id' => $review->id,
            'remark' => 'xxx',
            'clubRankId' => 1
        ];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(200);

        # disapprove
        $review = new ReviewMemberRegister();
        $review->account = 'xxx';
        $review->password = 'xxx';
        $review->name = 'xxx';
        $review->invitation_code = 'Md56ba7d';
        $review->gender = 'NA';
        $review->phone = 'xxx';
        $review->email = 'xxx';
        $review->qq = 'xxx';
        $review->wechat = 'xxx';
        $review->weibo = 'xxx';
        $review->review_step_count = 0;
        $review->review_step = 0;
        $review->review_role_id = 0;
        $review->status = 'pending';
        $review->saveOrError();
        $reviewMember = new ReviewKeyMemberRegisterService($review);
        $reviewMember->commit();

        $data = [
            'id' => $review->id,
            'reason' => 'xxx'
        ];
        $this->post(static::API_DISAPPROVE, $data)
            ->assertStatus(200);

        # list
        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
        $data = [
            'status' => 'disapproved',
            'page' => 1,
            'perPage' => 1,
            'sorts' => ['status,desc']
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # club-options
        $this->call('GET', static::API_CLUB_OPTIONS)
            ->assertStatus(200);

        # club-rank-options
        $data = [];
        $this->call('GET', static::API_CLUB_RANK_OPTIONS, $data)
            ->assertStatus(200);
        $data = ['id' => 1];
        $this->call('GET', static::API_CLUB_RANK_OPTIONS, $data)
            ->assertStatus(200);

        # step-log
        $data = [];
        $this->call('GET', static::API_STEP_LOG, $data)
            ->assertStatus(200);
    }
}