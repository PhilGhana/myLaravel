<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Agent;

class CompanyTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/company/add';
    const API_EDIT = '/api/company/edit';
    const API_EDIT_PASSWORD = '/api/company/edit-password';
    const API_TOGGLE_ENABLED = '/api/company/toggle-enabled';
    const API_TOGGLE_LOCKED = '/api/company/toggle-locked';
    const API_LIST = '/api/company/list';
    const API_ROLE_OPTIONS = '/api/company/role-options';

    public function testError()
    {
        # add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'account' => ['The account field is required.'],
                    'password' => ['The password field is required.'],
                    'name' => ['The name field is required.'],
                    'roleId' => ['The role id field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'locked' => ['The locked field is required.'],
                ],
                'message' => 'fail'
            ]);
            $data = [
                'account' => 'xxx',
                'password' => 'xxx',
                'name' => 'xxx',
                'roleId' => 'xxx',
                'enabled' => 'xxx',
                'locked' => 'xxx',
            ];
            $this->post(static::API_ADD, $data)
                ->assertStatus(400)
                ->assertExactJson([
                    'errors' => [
                        'roleId' => ['The selected role id is invalid.'],
                        'enabled' => ['The selected enabled is invalid.'],
                        'locked' => ['The selected locked is invalid.'],
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
                    'name' => ['The name field is required.'],
                    'roleId' => ['The role id field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'locked' => ['The locked field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'password' => 'xxx',
            'name' => 'xxx',
            'roleId' => 'xxx',
            'enabled' => 'xxx',
            'locked' => 'xxx',
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'roleId' => ['The selected role id is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'locked' => ['The selected locked is invalid.'],
                ],
                'message' => 'fail'
            ]);

        # edit-password
        $data = [];
        $this->post(static::API_EDIT_PASSWORD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'password' => ['The password field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'password' => 'xxx'
        ];
        $this->post(static::API_EDIT_PASSWORD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
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

        # toggle-locked
        $data = [];
        $this->post(static::API_TOGGLE_LOCKED, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'locked' => ['The locked field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'locked' => 'xxx'
        ];
        $this->post(static::API_TOGGLE_LOCKED, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'locked' => ['The selected locked is invalid.']
                ],
                'message' => 'fail'
            ]);

        # list
        $data = [
            'name' => 'xxx',
            'account' => 'xxx',
            'enabled' => 'xxx',
            'locked' => 'xxx',
            'roleId' => 'xxx',
            'page' => 'xxx',
            'perPage' => 'xxx',
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'enabled' => ['The selected enabled is invalid.'],
                    'locked' => ['The selected locked is invalid.'],
                    'roleId' => ['The selected role id is invalid.'],
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
    }

    public function testSuccess()
    {
        # login
        $this->post('/api/public/login', ['account' => 'ivan', 'password' => 'ivan']);

        # add
        $data = [
            'account' => 'xxx',
            'password' => 'xxx',
            'name' => 'xxx',
            'roleId' => 9,
            'enabled' => 1,
            'locked' => 0
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        $id = Agent::max('id');

        # edit
        $data = [
            'id' => $id,
            'password' => 'xxx',
            'name' => 'xxx',
            'roleId' => 9,
            'enabled' => 1,
            'locked' => 0
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(200);

        # toggle-enabled
        $data = [
            'id' => $id,
            'enabled' => 1
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(200);

        # toggle-locked
        $data = [
            'id' => $id,
            'locked' => 1
        ];
        $this->post(static::API_TOGGLE_LOCKED, $data)
            ->assertStatus(200);

        # list
        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
        $data = [
            'name' => 'xxx',
            'account' => 'xxx',
            'enabled' => 1,
            'locked' => 0,
            'roleId' => 1,
            'page' => 1,
            'perPage' => 1,
            'sorts' => ['name,desc']
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # role-options
        $this->call('GET', static::API_ROLE_OPTIONS)
            ->assertStatus(200);
    }
}