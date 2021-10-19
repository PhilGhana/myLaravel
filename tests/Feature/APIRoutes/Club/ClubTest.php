<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Game;
use App\Models\Club;
use App\Models\ClubRank;

class ClubTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/club/add';
    const API_EDIT = '/api/club/edit';
    const API_TOGGLE_ENABLED = '/api/club/toggle-enabled';
    const API_RANK_ADD = '/api/club/rank/add';
    const API_RANK_EDIT = '/api/club/rank/edit';
    const API_RANK_TOGGLE_ENABLED = '/api/club/rank/toggle-enabled';
    const API_LIST = '/api/club/list';
    const API_FIND = '/api/club/find';
    const API_RANK_LIST = '/api/club/rank/list';
    const API_RANK_FIND = '/api/club/rank/find';
    const API_GAME_PLATFORMS = '/api/club/game-platforms';

    const API_CLUB_RANK_DEFAULT = '/api/club/rank/default';
    const API_CLUB_RANK_MEMBERS = '/api/club/rank/members';

    public function testError()
    {
        # add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'name' => ['The name field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'games' => ['The games field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'name' => 'xxx',
            'enabled' => 'xxx',
            'games' => 'xxx'
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'enabled' => ['The selected enabled is invalid.'],
                    'games' => ['games not array or type error']
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
                    'enabled' => ['The enabled field is required.'],
                    'games' => ['The games field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'name' => 'xxx',
            'enabled' => 'xxx',
            'games' => 'xxx'
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'games' => ['games not array or type error']
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

        # rank/add
        $data = [];
        $this->post(static::API_RANK_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'clubId' => ['The club id field is required.'],
                    'name' => ['The name field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'default' => ['The default field is required.'],
                    'depositPerMax' => ['The deposit per max field is required.'],
                    'depositPerMin' => ['The deposit per min field is required.'],
                    'depositDayTimes' => ['The deposit day times field is required.'],
                    'withdrawPerMax' => ['The withdraw per max field is required.'],
                    'withdrawPerMin' => ['The withdraw per min field is required.'],
                    'withdrawDayTimes' => ['The withdraw day times field is required.'],
                    'configs' => ['The configs field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'clubId' => 'xxx',
            'name' => 'xxx',
            'enabled' => 'xxx',
            'default' => 'xxx',
            'order' => 'xxx',
            'depositPerMax' => 'xxx',
            'depositPerMin' => 'xxx',
            'depositDayTimes' => 'xxx',
            'withdrawPerMax' => 'xxx',
            'withdrawPerMin' => 'xxx',
            'withdrawDayTimes' => 'xxx',
            'configs' => 'xxx',
        ];
        $this->post(static::API_RANK_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'clubId' => ['The selected club id is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'default' => ['The selected default is invalid.'],
                    'order' => ['The order must be an integer.'],
                    'depositPerMax' => ['The deposit per max must be a number.'],
                    'depositPerMin' => ['The deposit per min must be a number.'],
                    'depositDayTimes' => ['The deposit day times must be an integer.'],
                    'withdrawPerMax' => ['The withdraw per max must be a number.'],
                    'withdrawPerMin' => ['The withdraw per min must be a number.'],
                    'withdrawDayTimes' => ['The withdraw day times must be an integer.'],
                    'configs' => ['The configs must be an array.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'clubId' => 1,
            'name' => 'xxx',
            'enabled' => 1,
            'default' => 1,
            'order' => 1,
            'depositPerMax' => 1,
            'depositPerMin' => 1,
            'depositDayTimes' => 1,
            'withdrawPerMax' => 1,
            'withdrawPerMin' => 1,
            'withdrawDayTimes' => 1,
            'configs' => [
                [
                    'xxx'
                ],
            ],
        ];
        $this->post(static::API_RANK_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "config-ids" => ["The selected config-ids is invalid."],
                    "config-waters.0" => ["The config-waters.0 field is required."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'clubId' => 1,
            'name' => 'xxx',
            'enabled' => 1,
            'default' => 1,
            'order' => 1,
            'depositPerMax' => 1,
            'depositPerMin' => 1,
            'depositDayTimes' => 1,
            'withdrawPerMax' => 1,
            'withdrawPerMin' => 1,
            'withdrawDayTimes' => 1,
            'configs' => [
                [
                    'id' => 'xxx',
                    'waterPercent' => 'xxx'
                ],
            ],
        ];
        $this->post(static::API_RANK_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "config-ids" => ["The selected config-ids is invalid."],
                    "config-waters.0" =>["The config-waters.0 must be a number."]
                ],
                'message' => 'fail'
            ]);

        # rank/edit
        $data = [];
        $this->post(static::API_RANK_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'name' => ['The name field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'default' => ['The default field is required.'],
                    'depositPerMax' => ['The deposit per max field is required.'],
                    'depositPerMin' => ['The deposit per min field is required.'],
                    'depositDayTimes' => ['The deposit day times field is required.'],
                    'withdrawPerMax' => ['The withdraw per max field is required.'],
                    'withdrawPerMin' => ['The withdraw per min field is required.'],
                    'withdrawDayTimes' => ['The withdraw day times field is required.'],
                    'configs' => ['The configs field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'name' => 'xxx',
            'enabled' => 'xxx',
            'default' => 'xxx',
            'order' => 'xxx',
            'depositPerMax' => 'xxx',
            'depositPerMin' => 'xxx',
            'depositDayTimes' => 'xxx',
            'withdrawPerMax' => 'xxx',
            'withdrawPerMin' => 'xxx',
            'withdrawDayTimes' => 'xxx',
            'configs' => 'xxx',
        ];
        $this->post(static::API_RANK_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'default' => ['The selected default is invalid.'],
                    'order' => ['The order must be an integer.'],
                    'depositPerMax' => ['The deposit per max must be a number.'],
                    'depositPerMin' => ['The deposit per min must be a number.'],
                    'depositDayTimes' => ['The deposit day times must be an integer.'],
                    'withdrawPerMax' => ['The withdraw per max must be a number.'],
                    'withdrawPerMin' => ['The withdraw per min must be a number.'],
                    'withdrawDayTimes' => ['The withdraw day times must be an integer.'],
                    'configs' => ['The configs must be an array.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 1,
            'name' => 'xxx',
            'enabled' => 1,
            'default' => 1,
            'order' => 1,
            'depositPerMax' => 1,
            'depositPerMin' => 1,
            'depositDayTimes' => 1,
            'withdrawPerMax' => 1,
            'withdrawPerMin' => 1,
            'withdrawDayTimes' => 1,
            'configs' => [
                [
                    'xxx'
                ],
            ],
        ];
        $this->post(static::API_RANK_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "config-ids" => ["The selected config-ids is invalid."],
                    "config-waters.0" => ["The config-waters.0 field is required."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 1,
            'name' => 'xxx',
            'enabled' => 1,
            'default' => 1,
            'order' => 1,
            'depositPerMax' => 1,
            'depositPerMin' => 1,
            'depositDayTimes' => 1,
            'withdrawPerMax' => 1,
            'withdrawPerMin' => 1,
            'withdrawDayTimes' => 1,
            'configs' => [
                [
                    'id' => 'xxx',
                    'waterPercent' => 'xxx'
                ],
            ],
        ];
        $this->post(static::API_RANK_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "config-ids" => ["The selected config-ids is invalid."],
                    "config-waters.0" => ["The config-waters.0 must be a number."]
                ],
                'message' => 'fail'
            ]);

        # rank/toggle-enabled
        $data = [];
        $this->post(static::API_RANK_TOGGLE_ENABLED, $data)
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
            'enabled' => 'xxx',
        ];
        $this->post(static::API_RANK_TOGGLE_ENABLED, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'enabled' => ['The selected enabled is invalid.']
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

        # find
        $data = [];
        $this->call('GET', static::API_FIND, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'not found'
            ]);

        # rank/find
        $data = [];
        $this->call('GET', static::API_RANK_FIND, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'not found'
            ]);
    }
    public function testSuccess()
    {
        # add
        $data = [
            'name' => 'xxx',
            'enabled' => 1,
            'games' => [99,100]
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        $id = Club::max('id');

        # edit
        $data = [
            'id' => $id,
            'name' => 'xxx',
            'enabled' => 1,
            'games' => [99,100]
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(200);

        # toggle-enabled
        $data = [
            'id' => $id,
            'enabled' => 1,
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(200);

        # rank/add
        $game = new Game();
        $game->platform_id = 1;
        $game->type = 'xxx';
        $game->code = 'xxx';
        $game->name = 'xxx';
        $game->free = 0;
        $game->hot = 0;
        $game->recent = 0;
        $game->enabled = 0;
        $game->maintain = 0;
        $game->saveOrError();
        $data = [
            'clubId' => $id,
            'name' => 'xxx',
            'enabled' => 1,
            'default' => 1,
            'depositPerMax' => 1,
            'depositPerMin' => 1,
            'depositDayTimes' => 1,
            'withdrawPerMax' => 1,
            'withdrawPerMin' => 1,
            'withdrawDayTimes' => 1,
            'configs' => [
                [
                    'id' => $game->id,
                    'waterPercent' => 0
                ]
            ],
        ];
        $this->post(static::API_RANK_ADD, $data)
            ->assertStatus(200);

        $clubRankId = ClubRank::max('id');

        # rank/edit
        $data = [
            'id' => $clubRankId,
            'name' => 'xxx',
            'enabled' => 1,
            'default' => 1,
            'depositPerMax' => 1,
            'depositPerMin' => 1,
            'depositDayTimes' => 1,
            'withdrawPerMax' => 1,
            'withdrawPerMin' => 1,
            'withdrawDayTimes' => 1,
            'configs' => [
                [
                    'id' => $game->id,
                    'waterPercent' => 0
                ]
            ],
        ];
        $this->post(static::API_RANK_EDIT, $data)
            ->assertStatus(200);

        # rank/toggle-enabled
        $data = [
            'id' => $clubRankId,
            'enabled' => 1,
        ];
        $this->post(static::API_RANK_TOGGLE_ENABLED, $data)
            ->assertStatus(200);

        # list
        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
        $data = [
            'name' => 'xxx',
            'enabled' => 1,
            'page' => 1,
            'perPage' => 1
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # find
        $data = [
            'id' => $id
        ];
        $this->call('GET', static::API_FIND, $data)
            ->assertStatus(200);

        # rank/list
        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
        $data = [
            'id' => 1
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # rank/find
        $data = [
            'id' => $clubRankId
        ];
        $this->call('GET', static::API_RANK_FIND, $data)
            ->assertStatus(200);

        # game-platforms
        $this->call('GET', static::API_GAME_PLATFORMS)
            ->assertStatus(200);

        # club-rank-default
        $this->get(static::API_CLUB_RANK_DEFAULT)
            ->assertStatus(200);

        # club-rank-members
        $data = [];
        $this->call('GET', static::API_CLUB_RANK_MEMBERS, $data)
            ->assertStatus(200);
        $data = [
            'id' => 1,
            'sorts' => ['id,desc'],
            'page' => 1,
            'perPage' => 1
        ];
        $this->call('GET', static::API_CLUB_RANK_MEMBERS, $data)
            ->assertStatus(200);
    }
}