<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Review\ReviewKeyMemberInformationService;
use App\Models\Review\ReviewMemberInformation;

class ReviewMemberInformationTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_APPROVE = '/api/review/member-information/approve';
    const API_DISAPPROVE = '/api/review/member-information/disapprove';
    const API_LIST = '/api/review/member-information/list';
    const API_STEP_LOG = '/api/review/member-information/step-log/all';

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
        $reviewMemberInformatiom = new ReviewMemberInformation();
        $reviewMemberInformatiom->member_id = 12;
        $reviewMemberInformatiom->name = 'xxx';
        $reviewMemberInformatiom->review_step_count = 0;
        $reviewMemberInformatiom->review_step = 0;
        $reviewMemberInformatiom->review_role_id = 0;
        $reviewMemberInformatiom->status = 'pending';
        $reviewMemberInformatiom->reason = '';
        $reviewMemberInformatiom->saveOrError();
        $review = new ReviewKeyMemberInformationService($reviewMemberInformatiom);
        $review->commit();

        $data = [
            'id' => $reviewMemberInformatiom->id,
            'remark' => 'xxx'
        ];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(200);

        # disapprove
        $reviewMemberInformatiom = new ReviewMemberInformation();
        $reviewMemberInformatiom->member_id = 12;
        $reviewMemberInformatiom->name = 'xxx';
        $reviewMemberInformatiom->review_step_count = 0;
        $reviewMemberInformatiom->review_step = 0;
        $reviewMemberInformatiom->review_role_id = 0;
        $reviewMemberInformatiom->status = 'pending';
        $reviewMemberInformatiom->reason = '';
        $reviewMemberInformatiom->saveOrError();
        $review = new ReviewKeyMemberInformationService($reviewMemberInformatiom);
        $review->commit();

        $data = [
            'id' => $reviewMemberInformatiom->id,
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