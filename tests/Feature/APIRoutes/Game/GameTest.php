<?php
namespace Tests\Feature\APIRoutes\PublicAPI;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Models\Agent;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\Models\GamePlatform;
use App\Models\GameType;
use App\Models\Game;
use App\Models\GameTag;



/**
 * 平台相關測試
 */
class GameTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/game/add';
    const API_EDIT = '/api/game/edit';
    const API_LIST = '/api/game/list';
    const API_TOGGLE_ENABLED = '/api/game/toggle-enabled';
    const API_TOGGLE_MAINTAIN = '/api/game/toggle-maintain';

    const API_GAME_TAG_OPTIONS = '/api/game/game-tag-options';
    const API_GAME_TYPE_OPTIONS = '/api/game/game-type-options';

    const API_PLATFORM_OPTIONS = '/api/game/platform-options';
    const API_ALL = '/api/game/all'; // 未完
    const API_ASSIGN_tAGS = '/api/game/assign-tags'; // 未完

    const PLATFORM_KEY = 'ut-key';
    const GAME_CODE = 'unit-test-code';

    public function testAdd ()
    {
        $testTags = ['hot', 'free'];
        $testType = 'slot';
        $this->generatorTestGameTags($testType, $testTags);

        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson(([
                'errors' => [
                    'code' => ['The code field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'maintain' => ['The maintain field is required.'],
                    'name' => ['The name field is required.'],
                    'order' => ['The order field is required.'],
                    'platformId' => ['The platform id field is required.'],
                    'type' => ['The type field is required.']
                ],
                'message' => 'fail'
            ]));


        # 測試 type, tags 一組時, tags 其中的 [aa] 值不存在
        $data = [
            'key' => static::PLATFORM_KEY,
            'platformId' => 0,
            'type' => $testType,
            'tags' => array_merge($testTags, ['aa']),
            'limit' => '-1',
            'maintain' => 'x',
            'order' => 'xd',
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'platformId' => ['The selected platform id is invalid.'],
                    'name' => ['The name field is required.'],
                    'code' => ['The code field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'order' => ['The order must be a number.'],
                    'maintain' => ['The selected maintain is invalid.'],
                    'tags' => ['The selected tags is invalid.'],
                ],
                'message' => 'fail'
            ]);

        # 測試 type, tags 一組時, type 不正確, tags 值皆存在
        $data = [
            'key' => static::PLATFORM_KEY,
            'platformId' => 0,
            'type' => 'no-exists',
            'tags' => $testTags,
            'limit' => '-1',
            'enabled' => 'x',
            'maintain' => 'x',
            'order' => 'xd',
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'platformId' => ['The selected platform id is invalid.'],
                    'name' => ['The name field is required.'],
                    'code' => ['The code field is required.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'order' => ['The order must be a number.'],
                    'type' => ['The selected type is invalid.'],
                    'tags' => ['The selected tags is invalid.'],
                    'maintain' => ['The selected maintain is invalid.'],
                ],
                'message' => 'fail'
            ]);


        # test when game.code exists
        $platform = $this->generatorTestPlatform();

        $game = new Game();
        $game->platform_id = $platform->id;
        $game->type = 'slot';
        $game->code = 'test-game';
        $game->name = 'test';
        $game->enabled = 1;
        $game->maintain = 1;
        $game->order = 0;
        $game->saveOrError();

        $data = [
            'key' => static::PLATFORM_KEY,
            'platformId' => $game->platform_id,
            'code' => 'test-game',
            'name' => 'test-game',
            'type' => $testType,
            'limit' => '0',
            'maintain' => '0',
            'enabled' => '0',
            'order' => '0',
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'code' => ['The code has already been taken.'],
                ],
                'message' => 'fail'
            ]);

        $data = [
            'key' => static::PLATFORM_KEY,
            'platformId' => $game->platform_id,
            'code' => 'test-game-a',
            'name' => 'test-game',
            'type' => $testType,
            'tags' => $testTags,
            'limit' => '0',
            'maintain' => '0',
            'enabled' => '0',
            'order' => '0',
        ];

        $this->post(static::API_ADD, $data)
            ->assertStatus(200);
    }
    public function testGameEdit ()
    {
        $platform = $this->generatorTestPlatform();
        $game = new Game();
        $game->platform_id = $platform->id;
        $game->type = 'slot';
        $game->code = 'test-game';
        $game->name = 'test';
        $game->enabled = 1;
        $game->maintain = 1;
        $game->order = 0;
        $game->saveOrError();

        $data = [
        ];


        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'code' => ['The code field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'id' => ['The id field is required.'],
                    'maintain' => ['The maintain field is required.'],
                    'name' => ['The name field is required.'],
                    'order' => ['The order field is required.'],
                    'type' => ['The type field is required.'],
                ],
                'message' => 'fail'
            ]);

    }


    public function testToggleEnabled ()
    {

        $game = new Game();
        $game->type = 'test';
        $game->platform_id = 0;
        $game->code = time();
        $game->name = 'test';
        $game->enabled = 0;
        $game->maintain = 0;
        $game->order = 0;
        $game->saveOrError();

        # test toggle enabled
        $enabledValue = 1;
        $data = [
            'id' => $game->id,
            'enabled' => $enabledValue,
        ];
        $this->json('post', static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(200);

        $game = $game->fresh();
        $this->assertEquals($game->enabled, $enabledValue);

        # test toggle disabled
        $disabledValue = 0;
        $data = [
            'id' => $game->id,
            'enabled' => $disabledValue,
        ];
        $this->json('post', static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(200);

        $game = $game->fresh();
        $this->assertEquals($game->enabled, $disabledValue);


    }

    public function testList ()
    {
        $page = 2;
        $perPage = 20;
        $data = [
            'page' => $page,
            'perPage' => $perPage,
        ];
        $this->json('get', static::API_LIST, $data)
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'perPage' => $perPage,
                    'page' => $page,
                ]
            ]);
    }

    public function testToggleMaintain ()
    {
        $platform = $this->generatorTestPlatform();
        $gtype = GameType::first();
        Game::where('code', static::GAME_CODE)->delete();
        $g1 = new Game();
        $g1->platform_id = $platform->id;
        $g1->type = $gtype->type;
        $g1->code = static::GAME_CODE;
        $g1->maintain = 0;
        $g1->enabled = 1;
        $g1->saveOrError();

        $g2 = new Game();
        $g2->platform_id = $platform->id;
        $g2->type = $gtype->type;
        $g2->code = static::GAME_CODE;
        $g2->maintain = 0;
        $g2->enabled = 1;
        $g2->saveOrError();

        $data = [
            'ids' => [$g1->id, $g2->id],
            'maintain' => 1,
        ];
        $this->post(static::API_TOGGLE_MAINTAIN, $data)
            ->assertStatus(200);

        $count = Game::where('code', static::GAME_CODE)
            ->where('maintain', 1)
            ->count();
        $this->assertEquals(2, $count);
        Game::where('code', static::GAME_CODE)->delete();
    }

    public function testGameTagOptions ()
    {
        $type = 'slot';
        $this->generatorTestGameTags($type, ['hot', 'free']);
        $tags = GameTag::select(['tag', 'type', 'name'])
            ->where('type', $type)
            ->orderBy('tag')
            ->get()
            ->toArray();

        $data = ['type' => $type];
        $this->json('get', static::API_GAME_TAG_OPTIONS, $data)
            ->assertStatus(200)
            ->assertExactJson([
                'data' => $tags,
            ]);
    }

    public function testGameTypeOptions ()
    {
        $this->json('get', static::API_GAME_TYPE_OPTIONS)
            ->assertStatus(200);
    }

    public function testPlatformOptions ()
    {
        $this->get(static::API_PLATFORM_OPTIONS)
            ->assertStatus(200);
    }

    /**
     * Undocumented function
     *
     * @return \App\Models\GamePlatform
     */
    protected function generatorTestPlatform()
    {
        $platform = new GamePlatform();
        $platform->key = static::PLATFORM_KEY;
        $platform->name = 'test';
        $platform->member_prefix = 'i88';
        $platform->paramter = '{}';
        $platform->enabled = 1;
        $platform->maintain = 0;
        $platform->limit = 0;
        $platform->order = 0;
        $platform->saveOrError();
        return $platform;
    }


    public function generatorTestGameTags ($type, $tags)
    {
        foreach ($tags as $tag) {
            $mTag = GameTag::where('tag', $tag)
                ->where('type', $type)
                ->first();
            $mTag->tag = $tag;
            $mTag->type = $type;
            $mTag->name = $tag;
            $mTag->saveOrError();
        }

    }
}
