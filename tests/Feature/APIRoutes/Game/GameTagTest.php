<?php
namespace Tests\Feature\APIRoutes\PublicAPI;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\Models\GamePlatform;
use App\Models\GameType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\GameTag;

/**
 * 平台相關測試
 */
class GameTagTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/game-tag/add';
    const API_EDIT = '/api/game-tag/edit';
    const API_LIST = '/api/game-tag/list';
    const API_GAME_TYPE_OPTIONS = '/api/game-tag/game-type-options';

    public function testAddValidatorError ()
    {
        # add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'tag' => ['The tag field is required.'],
                    'type' => ['The type field is required.'],
                    'name' => ['The name field is required.'],
                ],
                'message' => 'fail'
            ]);

        $data = [
            'tag' => '123456789012345678901',
            'type' => '12345678901',
            'name' => '123456789012345678901',
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'tag' => ['The tag may not be greater than 20 characters.'],
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
            'tag' => $gtype->tag,
            'type' => $gtype->type,
            'name' => $gtype->name,
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'tag' => ['The tag field is required.'],
                ],
                'message' => 'fail'
            ]);

        # edit
        $data = ['type' => 'aaaaaaaa'];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'tag' => ['The tag field is required.'],
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
                    'tag' => ['The tag field is required.'],
                    'type' => ['The selected type is invalid.'],
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
        $total = GameTag::count();

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
        $testTag = 'ttag';
        $testName = 'ttagName';

        $atype = new GameType();
        $atype->type = 'a-type';
        $atype->name = 'a-type';
        $atype->saveOrError();

        $btype = new GameType();
        $btype->type = 'b-type';
        $btype->name = 'b-type';
        $btype->saveOrError();

        $data = [
            'tag' => $testTag,
            'type' => $atype->type,
            'name' => $testName,
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        $data = [
            'tag' => $testTag,
            'type' => $btype->type,
            'name' => $testName,
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);


        $gtag = GameTag::where('type', $atype->type)
            ->where('tag', $testTag)
            ->first();
        $this->assertTrue(!empty($gtag));


        # edit
        $editName = 'test-tag-E';
        $data = [
            'tag' => $gtag->tag,
            'type' => $gtag->type,
            'name' => $editName,
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(200);


        # 檢查是否 edit 成功
        $atag = GameTag::where('tag', $testTag)
            ->where('type', $atype->type)
            ->first();
        $this->assertEquals($editName, $atag->name);

        # 檢查是否有影響到相同 tag 不同 type 的資料
        $btag = GameTag::where('tag', $testTag)
            ->where('type', $btype->type)
            ->first();
        $this->assertEquals($testName, $btag->name);

        # list
        $data = [
            'enabled' => 1,
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # game-type-options
        $this->get(static::API_GAME_TYPE_OPTIONS)
            ->assertStatus(200);
    }
}
