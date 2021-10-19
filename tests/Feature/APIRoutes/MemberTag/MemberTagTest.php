<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\MemberTag;

class MemberTagTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/member-tag/add';
    const API_EDIT = '/api/member-tag/edit';
    const API_LIST = '/api/member-tag/list';

    public function testError()
    {
        # add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'name' => ['The name field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'name' => 'xxx',
            'color' => 'xxx'
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'color' => ['The color format is invalid.'],
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
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'name' => 'xxx',
            'color' => 'xxx'
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'color' => ['The color format is invalid.']
                ],
                'message' => 'fail'
            ]);

        # list
        $data = [
            'page' => 'xxx',
            'perPage' => 'xxx'
        ];
        $this->call('GEt', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'page' => ['The page must be an integer.'],
                    'perPage' => ['The per page must be an integer.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'sorts' => ['...']
        ];
        $this->call('GEt', static::API_LIST, $data)
            ->assertStatus(500);
    }

    public function testSuccess()
    {
        # add
        $data = [
            'name' => 'xxx',
            'color' => '#AABBCC',
            'remark' => 'xxx'
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        $id = MemberTag::max('id');

        # edit
        $data = [
            'id' => $id,
            'name' => 'xxx',
            'color' => '#AABBCC',
            'remark' => 'xxx'
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(200);

        # list
        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
        $data = [
            'page' => 1,
            'perPage' => 1,
            'sorts' => ['id,desc']
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
    }
}