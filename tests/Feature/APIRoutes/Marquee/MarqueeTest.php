<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\Models\Marquee;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MarqueeTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/marquee/add';
    const API_EDIT = '/api/marquee/edit';
    const API_TOGGLE_ENABLED = '/api/marquee/toggle-enabled';
    const API_LIST = '/api/marquee/list';
    const API_ALL_AGENT = '/api/marquee/all/agent';

    const MARQUEE_TEST_KEY = 'marquee_test';

    public function testError()
    {
        # add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'site' => ['The site field is required.'],
                    'lang' => ['The lang field is required.'],
                    'type' => ['The type field is required.'],
                    'content' => ['The content field is required.'],
                    'enabled' => ['The enabled field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'site' => 'xxx',
            'lang' => 'xxx',
            'type' => 'xxx',
            'content' => 'xxx',
            'startTime' => 'xxx',
            'endTime' => 'xxx',
            'enabled' => 'xxx'
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'site' => ['The selected site is invalid.'],
                    'lang' => ['The selected lang is invalid.'],
                    'type' => ['The selected type is invalid.'],
                    'startTime' => ['The start time is not a valid date.'],
                    'endTime' => ['The end time is not a valid date.'],
                    'enabled' => ['The selected enabled is invalid.']
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
                    'site' => ['The site field is required.'],
                    'lang' => ['The lang field is required.'],
                    'type' => ['The type field is required.'],
                    'content' => ['The content field is required.'],
                    'enabled' => ['The enabled field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'site' => 'xxx',
            'lang' => 'xxx',
            'type' => 'xxx',
            'content' => 'xxx',
            'startTime' => 'xxx',
            'endTime' => 'xxx',
            'enabled' => 'xxx'
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'site' => ['The selected site is invalid.'],
                    'lang' => ['The selected lang is invalid.'],
                    'type' => ['The selected type is invalid.'],
                    'startTime' => ['The start time is not a valid date.'],
                    'endTime' => ['The end time is not a valid date.'],
                    'enabled' => ['The selected enabled is invalid.']
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
            'site' => 'xxx',
            'lang' => 'xxx',
            'type' => 'xxx',
            'enabled' => 'xxx',
            'page' => 'xxx',
            'perPage' => 'xxx'
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'site' => ['The selected site is invalid.'],
                    'lang' => ['The selected lang is invalid.'],
                    'type' => ['The selected type is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'page' => ['The page must be an integer.'],
                    'perPage' => ['The per page must be an integer.']
                ],
                'message' => 'fail'
            ]);

        # all/agent
        $data = ['lang' => 'xxx'];
        $this->call('GET', static::API_ALL_AGENT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'lang' => ['The selected lang is invalid.']
                ],
                'message' => 'fail'
            ]);
    }

    public function testSuccess()
    {
        # add
        $data = [
            'site' => 'web',
            'lang' => 'zh-tw',
            'type' => 'hot',
            'content' => static::MARQUEE_TEST_KEY,
            'startTime' => date('Y-m-d H:i:s'),
            'endTime' => date('Y-m-d H:i:s'),
            'enabled' => 1
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        $id = Marquee::max('id');

        # edit
        $data = [
            'id' => $id,
            'site' => 'web',
            'lang' => 'zh-tw',
            'type' => 'hot',
            'content' => static::MARQUEE_TEST_KEY,
            'startTime' => date('Y-m-d H:i:s'),
            'endTime' => date('Y-m-d H:i:s'),
            'enabled' => 1
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
        $data = [
            'site' => 'web',
            'lang' => 'zh-tw',
            'type' => 'hot',
            'enabled' => 1,
            'page' => 1,
            'perPage' => 1
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # all/agent
        $data = ['lang' => 'zh-cn'];
        $this->call('GET', static::API_ALL_AGENT, $data)
            ->assertStatus(200);

        # delete 測試資料
        Marquee::where('content', static::MARQUEE_TEST_KEY)->delete();
    }
}