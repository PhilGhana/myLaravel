<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Coupon;
use App\Models\CouponGroup;

class CouponTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/coupon-group/add';
    const API_EDIT = '/api/coupon-group/edit';
    const API_TOGGLE_ENABLED = '/api/coupon-group/toggle-enabled';
    const API_LIST = '/api/coupon-group/list';


    public function testAdd()
    {


        $data = [
            'name' => 'test-1225999',
            'enabled' => 0,
            'order' => 0,
        ];


        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

    }

    public function testEdit()
    {

        $group = $this->createCouponGroup();
        $data = $group->toArray();
        $this->post(static::API_EDIT, $data)
            ->assertStatus(200);

    }

    public function testToggleEnabled()
    {
        $group = $this->createCouponGroup();
        $data = [
            'id' => $group->id,
            'enabled' => $group->enabled,
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(200);

    }

    public function testList ()
    {
        $this->call('get', static::API_LIST, [
            'enabled' => -1,
            'sorts' => ['order,asc'],
        ])
        ->assertStatus(200);
    }


    private function createCouponGroup()
    {

        $group = new CouponGroup();
        $group->name = 'test';
        $group->order = 10;
        $group->enabled = 1;
        $group->saveOrError();
        return $group;

    }

}
