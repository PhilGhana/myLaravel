<?php
namespace Tests\Feature\APIRoutes\PublicAPI;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\Models\GamePlatform;
use App\Models\GameType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * 平台相關測試
 */
class GameTypeTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/game-type/add';
    const API_EDIT = '/api/game-type/edit';
    const API_LIST = '/api/game-type/list';


    public function testAddValidatorError ()
    {
        # add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'type' => ['The type field is required.'],
                    'name' => ['The name field is required.'],
                ],
                'message' => 'fail'
            ]);

        $data = [
            'type' => '12345678901',
            'name' => '123456789012345678901',
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'type' => ['The type may not be greater than 10 characters.'],
                    'name' => ['The name may not be greater than 20 characters.'],
                ],
                'message' => 'fail'
            ]);

        # test game_type.type unique
        $gtype = GameType::first();
        if (!$gtype) {
            $gtype = new GameType();
            $gtype->type = 'test-unique';
            $gtype->name = 'test-unique';
            $gtype->saveOrError();
        }
        $data = [
            'type' => $gtype->type,
            'name' => $gtype->name,
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'type' => ['The type has already been taken.'],
                ],
                'message' => 'fail'
            ]);

        # edit
        $data = ['type' => 'aaaaaaaa'];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'type' => ['The selected type is invalid.'],
                    'name' => ['The name field is required.'],
                ],
                'message' => 'fail'
            ]);

        $gtype = GameType::first();
        $data = [
            'type' => $gtype->type,
            'name' => '123456789012345678901',
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'name' => ['The name may not be greater than 20 characters.'],
                ],
                'message' => 'fail'
            ]);

    }

    public function testList ()
    {
        $page = 2;
        $perPage = 10;
        $data = [
            'perPage' => $perPage,
            'page' => $page,
        ];
        $total = GameType::count();

        $this->call('get', static::API_LIST, $data)
            ->assertJson([
                'data' => [
                    'total' => $total,
                    'page' => $page,
                    'perPage' => $perPage,
                ]
            ]);

    }


    public function testAddSuccess ()
    {
        $typeName = 'test-type';

        $data = [
            'type' => $typeName,
            'name' => 'test-type',
        ];
        $res = $this->post(static::API_ADD, $data)
            ->assertStatus(200);



        # edit
        $gtype = GameType::where('type', $typeName)->first();
        $this->assertTrue(!empty($gtype));

        $data = [
            'type' => $gtype->type,
            'name' => $gtype->name,
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(200);

        $editName = 'test-typeE';
        $data = [
            'type' => $gtype->type,
            'name' => $editName,
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(200);

        # 檢查是否 edit 成功
        $gtype = $gtype->fresh();
        $this->assertEquals($editName, $gtype->name);

        # list
        $data = [
            'enabled' => 1,
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
    }
}
