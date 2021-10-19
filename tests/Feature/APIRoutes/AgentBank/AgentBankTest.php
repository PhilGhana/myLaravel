<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\Models\AgentBank;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AgentBankTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/agent-bank/add';
    const API_EDIT = '/api/agent-bank/edit';
    const API_TOGGLE_ENABLED = '/api/agent-bank/toggle-enabled';
    const API_LIST = '/api/agent-bank/bank-list';

    public function testError()
    {
        # add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'name' => ['The name field is required.'],
                    'account' => ['The account field is required.'],
                    'bankName' => ['The bank name field is required.'],
                    'enabled' => ['The enabled field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'name' => 'xxx',
            'account' => 'xxx',
            'bankName' => 'xxx',
            'enabled' => 'xxx'
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
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
                    'name' => ['The name field is required.'],
                    'enabled' => ['The enabled field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'enabled' => 'xxx'
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'name' => ['The name field is required.'],
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
    }

    public function testSuccess()
    {
        # login
        $data = [
            'account' => 'admin',
            'password' => 'admin'
        ];
        $this->post('/api/public/login', $data);

        # add
        $data = [
            'name' => 'xxx',
            'account' => 'xxx',
            'bankName' => 'xxx',
            'branchName' => 'xxx',
            'phone' => 'xxx',
            'idCard' => 'xxx',
            'provinceName' => 'xxx',
            'cityName' => 'xxx',
            'enabled' => 1
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        $id = AgentBank::max('id');

        # edit
        $data = [
            'id' => $id,
            'name' => 'xxx',
            'phone' => 'xxx',
            'cityName' => 'xxx',
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
        $this->get(static::API_LIST)->assertStatus(200);
    }
}