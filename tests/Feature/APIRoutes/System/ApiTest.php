<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api;

class ApiTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/api/add';
    const API_EDIT = '/api/api/edit';
    const API_TOGGLE_ENABLED = '/api/api/toggle-enabled';
    const API_LIST = '/api/api/list';
    const API_INDEX_LIST = '/api/api/index-list';

    public function testError()
    {
        # add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'viewId' => ['The view id field is required.'],
                    'path' => ['The path field is required.'],
                    'method' => ['The method field is required.'],
                    'enabled' => ['The enabled field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'viewId' => 'xxx',
            'path' => 'xx',
            'method' => 'xxx',
            'enabled' => 'xxx',
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'viewId' => ['The selected view id is invalid.'],
                    'method' => ['The selected method is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
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
                    'viewId' => ['The view id field is required.'],
                    'path' => ['The path field is required.'],
                    'method' => ['The method field is required.'],
                    'enabled' => ['The enabled field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'viewId' => 'xxx',
            'path' => 'xxx',
            'method' => 'xxx',
            'enabled' => 'xxx',
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'viewId' => ['The selected view id is invalid.'],
                    'method' => ['The selected method is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
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
                    'enabled' => ['The selected enabled is invalid.'],
                ],
                'message' => 'fail'
            ]);

        # list
        $data = [
            'method' => 'xxx',
            'enabled' => 'xxx',
            'page' => 'xxx',
            'perPage' => 'xxx'
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'method' => ['The selected method is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'page' => ['The page must be an integer.'],
                    'perPage' => ['The per page must be an integer.'],
                ],
                'message' => 'fail'
            ]);
    }
    public function testSuccess()
    {
        # add
        $data = [
            'viewId' => 36,
            'path' => 'xxx',
            'method' => 'POST',
            'enabled' => 1
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        $id = Api::max('id');

        # edit
        $data = [
            'id' => $id,
            'viewId' => 36,
            'path' => 'xxx',
            'method' => 'POST',
            'enabled' => 1
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

        # list
        $this->call('GET', static::API_LIST)
            ->assertStatus(200);
        $data = [
            'method' => 'GET',
            'enabled' => 1,
            'page' => 1,
            'perPage' => 1
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # index-list
        $this->call('GET', static::API_INDEX_LIST)
            ->assertStatus(200);
    }
}