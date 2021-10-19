<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Review\ReviewKeyAgentCouponService;
use App\Models\Review\ReviewAgentCoupon;

class ReviewAgentCouponTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_APPROVE = '/api/review/agent-coupon/approve';
    const API_DISAPPROVE = '/api/review/agent-coupon/disapprove';
    const API_LIST = '/api/review/agent-coupon/list';
    const API_STEP_LOG = '/api/review/agent-coupon/step-log/all';

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
    }
    public function testSuccess()
    {
        # add test info
        $this->post('/api/public/login', ['account' => 'admin', 'password' => 'admin']);
        $agentCoupon = new ReviewAgentCoupon();
        $agentCoupon->agent_id = user()->model()->id;
        $agentCoupon->name = 'xxx';
        $agentCoupon->type = 'transfer';
        $agentCoupon->platform_id = 23;
        $agentCoupon->suitable_type = 'all';
        $agentCoupon->bonus_type = 'percent';
        $agentCoupon->bonus_percent = 0;
        $agentCoupon->bonus_amount = 0;
        $agentCoupon->bonus_max = 0;
        $agentCoupon->amount_min = 0;
        $agentCoupon->amount_max = 0;
        $agentCoupon->bet_valid_multiple = 0;
        $agentCoupon->max_times_day = 0;
        $agentCoupon->max_times_total = 0;
        $agentCoupon->start_time = date('Y-m-d H:i:s');
        $agentCoupon->end_time = date('Y-m-d H:i:s');
        $agentCoupon->content = '';
        $agentCoupon->enabled = 1;
        $agentCoupon->remark = '';
        $agentCoupon->review_step_count = 0;
        $agentCoupon->review_step = 0;
        $agentCoupon->review_role_id = 0;
        $agentCoupon->status = 'pending';
        $agentCoupon->reason = '';
        $agentCoupon->saveOrError();

        $review = new ReviewKeyAgentCouponService($agentCoupon);
        $review->commit();

        # approve
        $data = [
            'id' => $agentCoupon->id,
        ];
        $this->post(static::API_APPROVE, $data)
            ->assertStatus(200);

        # disapprove
        $agentCoupon = new ReviewAgentCoupon();
        $agentCoupon->agent_id = user()->model()->id;
        $agentCoupon->name = 'xxx';
        $agentCoupon->type = 'transfer';
        $agentCoupon->platform_id = 23;
        $agentCoupon->suitable_type = 'all';
        $agentCoupon->bonus_type = 'percent';
        $agentCoupon->bonus_percent = 0;
        $agentCoupon->bonus_amount = 0;
        $agentCoupon->bonus_max = 0;
        $agentCoupon->amount_min = 0;
        $agentCoupon->amount_max = 0;
        $agentCoupon->bet_valid_multiple = 0;
        $agentCoupon->max_times_day = 0;
        $agentCoupon->max_times_total = 0;
        $agentCoupon->start_time = date('Y-m-d H:i:s');
        $agentCoupon->end_time = date('Y-m-d H:i:s');
        $agentCoupon->content = '';
        $agentCoupon->enabled = 1;
        $agentCoupon->remark = '';
        $agentCoupon->review_step_count = 0;
        $agentCoupon->review_step = 0;
        $agentCoupon->review_role_id = 0;
        $agentCoupon->status = 'pending';
        $agentCoupon->reason = '';
        $agentCoupon->saveOrError();
        $review = new ReviewKeyAgentCouponService($agentCoupon);
        $review->commit();

        $data = [
            'id' => $agentCoupon->id,
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
            'perPage' => 1
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # step-log
        $data = [];
        $this->call('GET', static::API_STEP_LOG, $data)
            ->assertStatus(200);
    }
}