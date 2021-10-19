<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Bank;

class BankTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_ADD = '/api/bank/add';
    const API_EDIT = '/api/bank/edit';
    const API_TOGGLE_ENABLED = '/api/bank/toggle-enabled';
    const API_LIST = '/api/bank/list';
    const API_AGENTS = '/api/bank/agents';
    const API_CLUBS = '/api/bank/clubs';
    const API_CLUB_RANKS = '/api/bank/club-ranks';
    const API_USEABLE_AGENTS = '/api/bank/useable/agents';
    const API_USEABLE_CLUB_RANKS = '/api/bank/useable/club-ranks';

    public function testError()
    {
        # add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'type' => ['The type field is required.'],
                    'suitable' => ['The suitable field is required.'],
                    'useable' => ['The useable field is required.'],
                    'name' => ['The name field is required.'],
                    'account' => ['The account field is required.'],
                    'bankName' => ['The bank name field is required.'],
                    'branchName' => ['The branch name field is required.'],
                    'enabled' => ['The enabled field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'type' => 'xxx',
            'suitable' => 'xxx',
            'useable' => 'xxx',
            'name' => 'xxx',
            'account' => 'xxx',
            'bankName' => 'xxx',
            'branchName' => 'xxx',
            'enabled' => 'xxx',
            'clubRanks' => 'xxx',
            'agents' => 'xxx',
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'type' => ['The selected type is invalid.'],
                    'suitable' => ['The selected suitable is invalid.'],
                    'useable' => ['The selected useable is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'type' => 'deposit',
            'suitable' => 'agent',
            'useable' => 'agent',
            'name' => 'xxx',
            'account' => 'xxx',
            'bankName' => 'xxx',
            'branchName' => 'xxx',
            'enabled' => 1,
            'agents' => ['xxx']
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'agents' => ['The selected agents is invalid.']
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
                    'type' => ['The type field is required.'],
                    'suitable' => ['The suitable field is required.'],
                    'useable' => ['The useable field is required.'],
                    'enabled' => ['The enabled field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'type' => 'xxx',
            'suitable' => 'xxx',
            'useable' => 'xxx',
            'enabled' => 'xxx',
            'clubRanks' => 'xxx',
            'agents' => 'xxx',
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'type' => ['The selected type is invalid.'],
                    'suitable' => ['The selected suitable is invalid.'],
                    'useable' => ['The selected useable is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'type' => 'deposit',
            'suitable' => 'member',
            'useable' => 'club-rank',
            'name' => 'xxx',
            'account' => 'xxx',
            'bankName' => 'xxx',
            'branchName' => 'xxx',
            'enabled' => 1,
            'clubRanks' => ['xxx']
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'clubRanks' => ['The selected club ranks is invalid.']
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


        # list
        $data = [
            'enabled' => 'xxx',
            'type' => 'xxx',
            'suitable' => 'xxx',
            'name' => 'xxx',
            'bankName' => 'xxx',
            'page' => 'xxx',
            'perPage' => 'xxx',
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'enabled' => ['The selected enabled is invalid.'],
                    'type' => ['The selected type is invalid.'],
                    'suitable' => ['The selected suitable is invalid.'],
                    'page' => ['The page must be an integer.'],
                    'perPage' => ['The per page must be an integer.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'sorts' => ['...']
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(500);

        # useable/agents
        $data = [];
        $this->call('GET', static::API_USEABLE_AGENTS, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'not found'
            ]);

        # useable/club-ranks
        $data = [];
        $this->call('GET', static::API_USEABLE_CLUB_RANKS, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'not found'
            ]);
    }
    public function testSuccess()
    {
        # add
        $data = [
            'type' => 'deposit',
            'suitable' => 'agent',
            'useable' => 'agent',
            'name' => 'xxx',
            'account' => 'xxx',
            'bankName' => 'xxx',
            'branchName' => 'xxx',
            'enabled' => 1,
            'agents' => [5, 6]
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        $id = Bank::max('id');

        # edit
        $data = [
            'id' => $id,
            'type' => 'deposit',
            'suitable' => 'member',
            'useable' => 'club-rank',
            'name' => 'xxx',
            'account' => 'xxx',
            'bankName' => 'xxx',
            'branchName' => 'xxx',
            'enabled' => 1,
            'clubRanks' => [2, 3]
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        # toggle-enabled
        $data = [
            'id' => $id,
            'enabled' => 1
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(200);

        # list
        $this->get(static::API_LIST)->assertStatus(200);
        $data = [
            'enabled' => -1,
            'type' => 'all',
            'suitable' => 'all',
            'name' => 'xxx',
            'bankName' => 'xxx',
            'page' => 1,
            'perPage' => 1,
            'sorts' => ['enabled,desc']
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # agents
        $this->get(static::API_AGENTS)->assertStatus(200);
        $data = [
            'name' => 'xxx',
            'account' => 'xxx'
        ];
        $this->call('GET', static::API_AGENTS, $data)
            ->assertStatus(200);

        # clubs
        $this->get(static::API_CLUBS)->assertStatus(200);

        # club-ranks
        $this->get(static::API_CLUB_RANKS)->assertStatus(200);
        $data = ['clubId' => 1];
        $this->call('GET', static::API_CLUB_RANKS, $data)
            ->assertStatus(200);

        # useable/agents
        $data = ['id' => 1];
        $this->call('GET', static::API_USEABLE_AGENTS, $data)
            ->assertStatus(200);

        # useable/club-ranks
        $data = ['id' => 1];
        $this->call('GET', static::API_USEABLE_CLUB_RANKS, $data)
            ->assertStatus(200);
    }
}