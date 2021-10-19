<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Coupon;

class CouponTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/coupon/add';
    const API_EDIT = '/api/coupon/edit';
    const API_TOGGLE_ENABLED = '/api/coupon/toggle-enabled';
    const API_LIST = '/api/coupon/list';
    const API_FIND = '/api/coupon/find';
    const API_PLATFORM_OPTIONS = '/api/coupon/platform-options';
    const API_AGENTS = '/api/coupon/agents';
    const API_CLUBS = '/api/coupon/clubs';
    const API_CLUB_RANKS = '/api/coupon/club-ranks';
    const API_GROUP_OPTIONS = '/api/coupon/group-options';

    public function testError()
    {
        # login
        $this->post('/api/public/login', ['account' => 'larry', 'password' => 'ivan']);

        # add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'betValidMultiple' => ["The bet valid multiple field is required."],
                    'bonusType' => ["The bonus type field is required."],
                    'enabled' => ["The enabled field is required."],
                    'endTime' => ["The end time field is required."],
                    'maxTimesDay' => ["The max times day field is required."],
                    'maxTimesTotal' => ["The max times total field is required."],
                    'memberRegisterEnd' => ["The member register end field is required."],
                    'memberRegisterStart' => ["The member register start field is required."],
                    'name' => ["The name field is required."],
                    'platformId' => ["The platform id field is required."],
                    'startTime' => ["The start time field is required."],
                    'suitableType' => ["The suitable type field is required."],
                    'type' => ["The type field is required."],
                    'memberRegisterStart' => ['The member register start field is required.'],
                    'memberRegisterEnd' => ['The member register end field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'name' => 'xxx',
            'type' => 'xxx',
            'image' => 'xxx',
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
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "image" => ["The image must be an image."],
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
            $this->post(static::API_ADD, $data)
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

        # edit
        $data = [];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'betValidMultiple' => ["The bet valid multiple field is required."],
                    'bonusType' => ["The bonus type field is required."],
                    'enabled' => ["The enabled field is required."],
                    'endTime' => ["The end time field is required."],
                    'maxTimesDay' => ["The max times day field is required."],
                    'maxTimesTotal' => ["The max times total field is required."],
                    'memberRegisterEnd' => ["The member register end field is required."],
                    'memberRegisterStart' => ["The member register start field is required."],
                    'name' => ["The name field is required."],
                    'platformId' => ["The platform id field is required."],
                    'startTime' => ["The start time field is required."],
                    'suitableType' => ["The suitable type field is required."],
                    'type' => ["The type field is required."],
                    'memberRegisterStart' => ['The member register start field is required.'],
                    'memberRegisterEnd' => ['The member register end field is required.']
                ],
                'message' => 'fail'
            ]);
            $data = [
                'id' => 'xxx',
                'name' => 'xxx',
                'type' => 'xxx',
                'image' => 'xxx',
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
            $this->post(static::API_EDIT, $data)
                ->assertStatus(400)
                ->assertExactJson([
                    'errors' => [
                        'id' => ['The selected id is invalid.'],
                        "image" => ["The image must be an image."],
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
                    'id' => 'xxx',
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
                $this->post(static::API_EDIT, $data)
                ->assertStatus(400)
                ->assertExactJson([
                    'errors' => [
                        'id' => ['The selected id is invalid.'],
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

        # toggle-enabled
        $data = [];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'enabled' => ['The enabled field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'enabled' => 'xxx'
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'enabled' => ['The selected enabled is invalid.']
                ],
                'message' => 'fail'
            ]);

        # list
        $data = [
            'type' => 'xxx',
            'platformId' => 'xxx',
            'page' => 'xxx',
            'perPage' => 'xxx'
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'type' => ['The selected type is invalid.'],
                    'platformId' => ['The platform id must be an integer.'],
                    'page' => ['The page must be an integer.'],
                    'perPage' => ['The per page must be an integer.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'sorts' => ['...']
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(500);

        # find
        $data = [];
        $this->call('GET', static::API_FIND, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'not found'
            ]);
    }
    public function testSuccess()
    {
        # login
        $this->post('/api/public/login', ['account' => 'larry', 'password' => 'ivan']);

        # add
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
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        $id = Coupon::max('id');

        # edit
        $data = [
            'id' => $id,
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
        $this->post(static::API_EDIT, $data)
            ->assertStatus(200);

        # toggle-enabled
        $data = [
            'id' => $id,
            'enabled' => 0
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(200);

        # list
        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
        $data = [
            'type' => 'all',
            'platformId' => 1,
            'sorts' => ['type,desc'],
            'page' => 1,
            'perPgae' => 1
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # find
        $data = ['id' => 3];
        $this->call('GET', static::API_FIND, $data)
            ->assertStatus(200);

        # platform-options
        $this->call('GET', static::API_PLATFORM_OPTIONS)
            ->assertStatus(200);

        # agents
        $data = [
            'account' => 'xxx',
            'name' => 'xxx'
        ];
        $this->call('GET', static::API_AGENTS, $data)
            ->assertStatus(200);

        # clubs
        $this->call('GET', static::API_CLUBS)
            ->assertStatus(200);

        # club-ranks
        $data = [
            'clubId' => 1
        ];
        $this->call('GET', static::API_CLUB_RANKS, $data)
            ->assertStatus(200);
    }

    public function testGroupCoupon ()
    {
        $this->call('get', static::API_GROUP_OPTIONS)
            ->assertStatus(200);
    }
}
