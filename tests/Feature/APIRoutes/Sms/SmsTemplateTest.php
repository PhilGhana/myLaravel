<?php

namespace Tests\Feature\APIRoutes\Sms;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

/**
 * 發信文本管理測試
 */
class SmsTemplateTest extends TestCase
{
    use DatabaseTransactions;
    use WithoutMiddleware;

    const API_ADD = '/api/sms-template/add';
    const API_EDIT = '/api/sms-template/edit';
    const API_TOGGLE_ENABLED = '/api/sms-template/toggle-enabled';
    const API_FIND = '/api/sms-template/find';
    const API_LIST = '/api/sms-template/list';
    const API_USER_OPTIONS = '/api/sms-template/sms-user-options';

    /**
     * add
     *
     * @return void
     */
    public function testAdd()
    {
        // -------------- error --------------

        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'key' => ['The key field is required.'],
                    'smsUserId' => ['The sms user id field is required.'],
                    'name' => ['The name field is required.'],
                    'content' => ['The content field is required.'],
                    'enabled' => ['The enabled field is required.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'key' => 1,
            'smsUserId' => 999,
            'name' => 123,
            'content' => 321,
            'enabled' => 9,
            'remark' => ''
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'key' => ['The key must be a string.'],
                    'smsUserId' => ['The selected sms user id is invalid.'],
                    'name' => ['The name must be a string.'],
                    'content' => ['The content must be a string.'],
                    'enabled' => ['The selected enabled is invalid.']
                ],
                'message' => 'fail'
            ]);

        // -------------- success --------------

        $data = [
            'key' => 'testKey',
            'smsUserId' => 4,
            'name' => 'testName',
            'content' => 'contentttttt',
            'enabled' => 1,
            'remark' => ''
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);
    }

    /**
     * edit
     *
     * @return void
     */
    public function testEdit()
    {
        // -------------- error --------------
        $data = [];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'smsUserId' => ['The sms user id field is required.'],
                    'name' => ['The name field is required.'],
                    'content' => ['The content field is required.'],
                    'enabled' => ['The enabled field is required.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'id' => 9999,
            'smsUserId' => 8888,
            'name' => 'testName',
            'content' => '',
            'enabled' => 99
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'smsUserId' => ['The selected sms user id is invalid.'],
                    'content' => ['The content field is required.'],
                    'enabled' => ['The selected enabled is invalid.']
                ],
                'message' => 'fail'
            ]);

        // -------------- success --------------
        $data = [
            'id' => 2,
            'smsUserId' => 3,
            'name' => 'nnnnn',
            'content' => 'ccccccccccccccccccccccccccc',
            'enabled' => 0,
            'remark' => 'rrrrr'
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(200);
    }

    /**
     * toggleEnabled
     *
     * @return void
     */
    public function testToggleEnabled()
    {
        // -------------- error --------------
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
            'id' => 999,
            'enabled' => 99
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

        // -------------- success --------------

        $data = [
            'id' => 2,
            'enabled' => 1
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(200);
    }

    /**
     * find
     *
     * @return void
     */
    public function testFind()
    {
        // -------------- error --------------

        $data = [];
        $this->call('GET', static::API_FIND, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'not found'
            ]);

        $data = [
            'id' => null
        ];
        $this->call('GET', static::API_FIND, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'not found'
            ]);

        // -------------- success --------------

        $data = [
            'id' => 3
        ];
        $this->call('GET', static::API_FIND, $data)
            ->assertStatus(200);
    }

    /**
     * getList
     *
     * @return void
     */
    public function testGetList()
    {
        // -------------- error --------------

        $data = [
            'enabled' => 9,
            'page' => '999',
            'perPage' => ''
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'enabled' => ['The selected enabled is invalid.']
                ],
                'message' => 'fail'
            ]);


        // -------------- success --------------

        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
    }


    /**
     * getUserOptions
     *
     * @return void
     */
    public function testGetUserOptions()
    {

        // -------------- success --------------

        $this->call('GET', static::API_USER_OPTIONS)
            ->assertStatus(200);
    }
}
