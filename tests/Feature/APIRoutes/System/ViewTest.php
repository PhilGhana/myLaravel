<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\View;

class ViewTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/view/add';
    const API_EDIT = '/api/view/edit';
    const API_TOGGLE_ENABLED = '/api/view/toggle-enabled';
    const API_LIST = '/api/view/list';
    const API_VIEW_OPTIONS = '/api/view/view-options';

    public function testError()
    {
        # add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'parentId' => ['The parent id field is required.'],
                    'name' => ['The name field is required.'],
                    'type' => ['The type field is required.'],
                    'enabled' => ['The enabled field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'parentId' => 'xxx',
            'name' => 'xxx',
            'type' => 'xxx',
            'enabled' => 'xxx',
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'parentId' => ['The selected parent id is invalid.'],
                    'type' => ['The selected type is invalid.'],
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
                    'name' => ['The name field is required.'],
                    'type' => ['The type field is required.'],
                    'enabled' => ['The enabled field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'parentId' => 'xxx',
            'name' => 'xxx',
            'type' => 'xxx',
            'enabled' => 'xxx',
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'parentId' => ['The selected parent id is invalid.'],
                    'type' => ['The selected type is invalid.'],
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
            'enabled' => 'xxx',
            'page' => 'xxx',
            'perPage' => 'xxx'
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'enabled' => ['The selected enabled is invalid.'],
                    'page' => ['The page must be an integer.'],
                    'perPage' => ['The per page must be an integer.'],
                ],
                'message' => 'fail'
            ]);

        # view-options
        $data = [
            'type' => 'xxx'
        ];
        $this->call('GET', static::API_VIEW_OPTIONS, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'type' => ['The selected type is invalid.']
                ],
                'message' => 'fail'
            ]);
    }
    public function testSuccess()
    {
        # add
        $data = [
            'parentId' => 0,
            'name' => 'xxx',
            'type' => 1,
            'enabled' => 1
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        $id = View::max('id');

        # edit
        $data = [
            'id' => $id,
            'parentId' => 31,
            'name' => 'xxx',
            'type' => 1,
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
            'name' => 'xxx',
            'enabled' => 1,
            'page' => 1,
            'perPage' => 1
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # view-options
        $data = [
            'type' => 1
        ];
        $this->call('GET', static::API_VIEW_OPTIONS, $data)
            ->assertStatus(200);
    }
}