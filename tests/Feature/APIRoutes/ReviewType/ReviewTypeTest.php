<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReviewTypeTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/review-type/add';
    const API_EDIT = '/api/review-type/edit';
    const API_TOGGLE_ENABLED = '/api/review-type/toggle-enabled';
    const API_ROLES = '/api/review-type/roles';
    const API_ALL = '/api/review-type/all';

    public function testError()
    {
        # add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'key' => ['The key field is required.'],
                    'name' => ['The name field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'steps' => ['The steps field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'key' => 'AgentDepositBank',
            'name' => 'xxxx',
            'enabled' => 'xxx',
            'steps' => ['xxx']
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'key' => ['The key has already been taken.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'steps' => ['steps not array or type error'],
                ],
                'message' => 'fail'
            ]);

        # edit
        $data = [];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'key' => ['The key field is required.'],
                    'name' => ['The name field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'steps' => ['The steps field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'key' => 'xxx',
            'name' => 'xxx',
            'enabled' => 'xxx',
            'steps' => 'xxx',
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'key' => ['The selected key is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'steps' => ['steps not array or type error'],
                ],
                'message' => 'fail'
            ]);

        # toggle-enabled
        $data = [];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'key' => ['The key field is required.'],
                    'enabled' => ['The enabled field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'key' => 'xxx',
            'enabled' => 'xxx'
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'key' => ['The selected key is invalid.'],
                    'enabled' => ['The selected enabled is invalid.']
                ],
                'message' => 'fail'
            ]);
    }
    public function testSuccess()
    {
        # add
        $data = [
            'key' => 'xxx',
            'name' => 'xxx',
            'enabled' => 1,
            'steps' => [9]
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        # edit
        $data = [
            'key' => 'xxx',
            'name' => 'xxx',
            'enabled' => 1,
            'steps' => [9]
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(200);

        # toggle-enabled
        $data = [
            'key' => 'MemberRegister',
            'enabled' => 1
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(200);

        # role
        $this->get(static::API_ROLES)->assertStatus(200);

        # all
        $this->get(static::API_ALL)->assertStatus(200);
    }
}