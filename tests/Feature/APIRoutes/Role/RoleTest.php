<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\Models\Role;
use App\Models\RoleApi;
use App\Models\RoleView;

class RoleTest extends TestCase
{
    use WithoutMiddleware;
    const API_ADD = '/api/role/add';
    const API_EDIT = '/api/role/edit';
    const API_AUTH = '/api/role/auth';
    const API_TOGGLE_ENABLED = '/api/role/toggle-enabled';
    const API_LIST = '/api/role/list';
    const API_FIND = '/api/role/find';
    const API_VIEW_ALL = '/api/role/view-all';
    const API_API_ALL = '/api/role/api-all';

    const ROLE_TEST_KEY = 'role_test';

    public function testValidatorError()
    {
        # add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'name' => ['The name field is required.'],
                'rank' => ['The rank field is required.'],
                'enabled' => ['The enabled field is required.']
            ]));

        $data = [
            'name' => '321',
            'rank' => 'asdf',
            'enabled' => 'adg'
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'rank' => ['The rank must be an integer.'],
                'enabled' => ['The selected enabled is invalid.']
            ]));

        # edit
        $data = [];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'id' => ['The id field is required.'],
                'name' => ['The name field is required.'],
                'rank' => ['The rank field is required.'],
                'enabled' => ['The enabled field is required.']
            ]));

        $data = [
            'id' => 'asd',
            'name' => '321',
            'rank' => 'asdf',
            'enabled' => 'adg'
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'id' => ['The selected id is invalid.'],
                'rank' => ['The rank must be an integer.'],
                'enabled' => ['The selected enabled is invalid.']
            ]));

        # toggle-enabled
        $data = [];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'id' => ['The id field is required.'],
                'enabled' => ['The enabled field is required.']
            ]));

        $data = [
            'id' => 'asd',
            'enabled' => 'adg'
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'id' => ['The selected id is invalid.'],
                'enabled' => ['The selected enabled is invalid.']
            ]));

        # auth
        $data = [];
        $this->post(static::API_AUTH, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'id' => ['The id field is required.'],
                'views' => ['The views field is required.'],
                'apis' => ['The apis field is required.'],
            ]));

        $data = [
            'id' => 'asd',
            'views' => ['a', 's', 4],
            'apis' => 's'
        ];
        $this->post(static::API_AUTH, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'id' => ['The selected id is invalid.'],
                'views' => ['views not array or type error'],
                'apis' => ['apis not array or type error'],
            ]));

        # list
        $data = [
            'name' => 'xxx',
            'enabled' => 'xxx',
            'sorts' => ['xxx,xxx'],
            'page' => 'xxx',
            'perPage' => 'xxx'
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'enabled' => ['The selected enabled is invalid.'],
                'sorts' => ['sorts not array or value error'],
                'page' => ['The page must be an integer.'],
                'perPage' => ['The per page must be an integer.']
            ]));

        # find
        $data = [];
        $this->call('GET', static::API_FIND, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'not found'
            ]));
        $data = ['id' => 'xxx'];
        $this->call('GET', static::API_FIND, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'not found'
            ]));
    }

    public function testSuccess ()
    {
        # login
        $this->post('/api/public/login', ['account' => 'admin', 'password' => 'admin']);

        # add
        $data = [
            'name' => static::ROLE_TEST_KEY,
            'rank' => 2,
            'enabled' => 1
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        # 測試資料ID
        $id = Role::max('id');

        # edit
        $data = [
            'id' => $id,
            'name' => static::ROLE_TEST_KEY,
            'rank' => 3,
            'enabled' => 1
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(200);

        # toggle-enable
        $data = [
            'id' => $id,
            'enabled' => 1
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(200);

        # auth
        $data = [
            'id' => $id,
            'views' => [4,5,6],
            'apis' => [5,6,7]
        ];
        $this->post(static::API_AUTH, $data)
            ->assertStatus(200);

        # list
        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
        $data = [
            'name' => 'xxx',
            'enabled' => 1,
            'sorts' => ['name,asc'],
            'page' => 1,
            'perPage' => 1
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        #find
        $data = ['id' => 1];
        $this->call('GET', static::API_FIND, $data)
            ->assertStatus(200);

        # view-all
        $this->call('GET', static::API_VIEW_ALL)->assertStatus(200);

        # api-all
        $this->call('GET', static::API_API_ALL)->assertStatus(200);

        # delete 測試資料
        Role::where('name', static::ROLE_TEST_KEY)->delete();
        RoleApi::where('role_id', $id)->delete();
        RoleView::where('role_id', $id)->delete();
    }
}