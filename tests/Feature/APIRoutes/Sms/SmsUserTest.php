<?php

namespace Tests\Feature\APIRoutes\Sms;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

/**
 * 簡訊平台帳號管理測試
 */
class SmsUserTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    // const API_LOGIN = '/api/public/login';
    const API_ADD = '/api/sms-user/add';
    const API_EDIT = '/api/sms-user/edit';
    const API_TOGGLE_ENABLED = '/api/sms-user/toggle-enabled';
    const API_LIST = '/api/sms-user/list';
    const API_MODULE_OPTIONS = '/api/sms-user/module-options';

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
                    'moduleId' => ['The module id field is required.'],
                    'username' => ['The username field is required.'],
                    'password' => ['The password field is required.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'moduleId' => 99,
            'username' => 888,
            'password' => 'abcdefghi12345678999999999999999999999999999999999999',
            'signature' => 123
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'moduleId' => ['The selected module id is invalid.'],
                    'username' => ['The username must be a string.'],
                    'password' => ['The password may not be greater than 50 characters.'],
                    'signature' => ['The signature must be a string.']
                ],
                'message' => 'fail'
            ]);

        // -------------- success --------------
        $data = [
            'moduleId' => 2,
            'username' => 'test123',
            'password' => 'test123',
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
                    'moduleId' => ['The module id field is required.'],
                    'username' => ['The username field is required.'],
                    "password" => ["The password field is required."]
                ],
                'message' => 'fail'
            ]);

        $data = [
            'id' => 999,
            'moduleId' => 999,
            'username' => 'test',
            'password' => '8787',
            'signature' => 123
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'moduleId' => ['The selected module id is invalid.'],
                    'signature' => ['The signature must be a string.']
                ],
                'message' => 'fail'
            ]);

        // -------------- success --------------

        $data = [
            'id' => 4,
            'moduleId' => 3,
            'username' => 'test123',
            'password' => 'test123',
            'signature' => ''
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
            'enabled' => -1
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
            'id' => 3,
            'enabled' => 0
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
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
            'moduleId' => 'xxx',
            'enabled' => 9,
            'page' => 999
        ];

        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'enabled' => ['The selected enabled is invalid.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'sorts' => ['...']
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(500);


        // -------------- success --------------

        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
    }

    /**
     * getModuleOptions
     *
     * @return void
     */
    public function testGetModuleOptions()
    {

        // -------------- success --------------

        $this->call('GET', static::API_MODULE_OPTIONS, [])
            ->assertStatus(200);
    }
}
