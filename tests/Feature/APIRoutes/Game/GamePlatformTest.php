<?php
namespace Tests\Feature\APIRoutes\PublicAPI;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\Models\GamePlatform;
use App\Models\GameType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Member;



/**
 * 平台相關測試
 */
class GamePlatformTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/game-platform/add';
    const API_EDIT = '/api/game-platform/edit';
    const API_TOGGLE_ENABLED = '/api/game-platform/toggle-enabled';
    const API_LIST = '/api/game-platform/list';
    const API_LIMIT_MEMBERS = '/api/game-platform/limit-members';
    const API_TYPES = '/api/game-platform/types';
    const API_PLATFORM_OPTIONS = '/api/game-platform/platform-options';
    const API_QUERY_MEMBER = '/api/game-platform/query-member';

    const PLATFORM_KEY = 'test';

    public function testAddValidatorError ()
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
                    'limit' => ['The limit field is required.'],
                    'maintain' => ['The maintain field is required.'],
                    'order' => ['The order field is required.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'key' => static::PLATFORM_KEY,
            'name' => 'test',
            'enabled' => 'x',
            'limit' => '-1',
            'maintain' => 'x',
            'order' => '1',
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'enabled' => ['The selected enabled is invalid.'],
                    'limit' => ['The selected limit is invalid.'],
                    'maintain' => ['The selected maintain is invalid.'],
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
                    'key' => ['The key field is required.'],
                    'name' => ['The name field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'limit' => ['The limit field is required.'],
                    'maintain' => ['The maintain field is required.'],
                    'order' => ['The order field is required.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'id' => 'asd',
            'key' => static::PLATFORM_KEY,
            'name' => 'test',
            'enabled' => 'x',
            'limit' => '-1',
            'maintain' => 'x',
            'order' => '1',
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'limit' => ['The selected limit is invalid.'],
                    'maintain' => ['The selected maintain is invalid.'],
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
                    'enabled' => ['The enabled field is required.'],
                ],
                'message' => 'fail'
            ]);

        $data = [
            'id' => 'asd',
            'enabled' => 'x',
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
            'enabled' => 'xasd',
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'enabled' => ['The selected enabled is invalid.'],
                ],
                'message' => 'fail'
            ]);

    }

    public function testQueryMember ()
    {
        $account = '';

        $members = Member::where('account', 'LIKE', "%{$account}%")
            ->select('id', 'account')
            ->take(10)
            ->orderBy('account')
            ->get()
            ->toArray();
        $this->json('get', static::API_QUERY_MEMBER, ['account' => $account])
            ->assertExactJson([
                'data' => $members
            ]);
    }

    public function testAddSuccess ()
    {
        $data = [
            'namespace' => '\\Test\\Run',
            'key' => static::PLATFORM_KEY,
            'name' => 'test',
            'member_prefix' => 'i88',
            'enabled' => 1,
            'maintain' => 1,
            'limit' => 0,
            'order' => 90000,
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);
            // ->assertSee('hi');

        # edit
        $id = GamePlatform::max('id');
        $data = [
            'id' => $id,
            'namespace' => '\\Test\\Run',
            'key' => static::PLATFORM_KEY,
            'name' => 'test',
            'member_prefix' => 'i88',
            'enabled' => 1,
            'maintain' => 1,
            'limit' => 0,
            'order' => 90000,
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(200);

        # toggle-enabled
        $id = GamePlatform::max('id');
        $data = [
            'id' => $id,
            'enabled' => 1,
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(200);

        # list
        $data = [
            'enabled' => 1,
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
    }
}
