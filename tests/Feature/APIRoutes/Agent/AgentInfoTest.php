<?php

namespace Tests\Feature\APIRoutes\Agent;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\AgentWallet;
use App\Models\Agent;

class AgentInfoTest extends TestCase
{

    use DatabaseTransactions;
    use WithoutMiddleware;

    const API_LOGIN = '/api/public/login';
    const API_NAME = '/api/info/name';
    const API_PWD = '/api/info/password';
    const API_LIST = '/api/info/log-wallet/list';
    const API_WALLET = '/api/info/wallet';


    /**
     * Test AgentInfo Error
     *
     * @return void
     */
    public function testAgentInfoError()
    {

        # agent login
        $this->post(static::API_LOGIN, ['account' => 'chloe', 'password' => 'chloe'])->assertStatus(200);

        # edit name
        $data = [];
        $this->post(static::API_NAME, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'name' => ['The name field is required.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'name' => 123
        ];
        $this->post(static::API_NAME, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'name' => ['The name must be a string.']
                ],
                'message' => 'fail'
            ]);

        # edit password
        $data = [];
        $this->post(static::API_PWD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'oldPassword' => ['The old password field is required.'],
                    'newPassword' => ['The new password field is required.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'oldPassword' => '123',
            'newPassword' => 'bbb'
        ];
        $this->post(static::API_PWD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'password incorrect'
            ]);

        $data = [
            'oldPassword' => 222,
            'newPassword' => 111
        ];
        $this->post(static::API_PWD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'oldPassword' => ['The old password must be a string.'],
                    'newPassword' => ['The new password must be a string.']
                ],
                'message' => 'fail'
            ]);

        # get wallet list
        $data = [
            'startTime' => 11,
            'endTime' => 00,
            'type' => 123
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'startTime' => ['The startTime is not a valid date.'],
                    'endTime' => ['The endTime is not a valid date.'],
                    'type' => ['The type must be a string.', 'The selected type is invalid.'],
                ],
                'message' => 'fail'
            ]);
    }

    /**
     * Test agent success
     *
     * @return void
     */
    public function testAgentInfoSuccess()
    {

        # agent login
        $account = 'chloe';
        $password = 'chloe';
        $this->post(static::API_LOGIN, ['account' => $account, 'password' => $password])->assertStatus(200);

        # add
        $data = ['name' => 'test123'];
        $this->post(static::API_NAME, $data)
            ->assertStatus(200);

        # edit password
        $data = [
            'oldPassword' => 'chloe',
            'newPassword' => 'chloe123'
        ];
        $this->post(static::API_PWD, $data)
            ->assertStatus(200);

        # get wallet list
        $data = [
            'startTime' => '2018-01-01',
            'endTime' => '2018-12-31',
            'sorts' => ['created_at,asc'],
            'type' => 'give-money',
            'page' => '',
            'perPage' => ''
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'content' => [
                        [
                            'id',
                            'type',
                            'changeSettlementAmount',
                            'beforeSettlementAmount',
                            'afterSettlementAmount',
                            'changeMoney',
                            'beforeMoney',
                            'afterMoney',
                            'editorId',
                            'editorAccount',
                            'ip',
                            'remark',
                            'createdAt',
                        ]
                    ],
                    'page',
                    'perPage',
                    'total'
                ]
            ]);

        # wallet
        $id = 3;
        $agentWallet = AgentWallet::findOrError($id);
        $agentWallet->settlement_amount = 100;
        $agentWallet->money = 300;
        $agentWallet->save();

        $this->call('GET', static::API_WALLET)
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    'money' => '300.0000',
                    'settlementAmount' => '100.0000'
                ]
            ]);
    }
}
