<?php

namespace Tests\Feature\APIRoutes\Agent;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\Models\AgentWallet;
use App\Models\Agent;
use App\Models\AgentIpWhitelist;

class AgentTest extends TestCase
{
    use DatabaseTransactions;
    use WithoutMiddleware;

    const API_LOGIN = '/api/public/login';
    const API_LOGOUT = '/api/public/logout';
    const API_ADD = '/api/agent/add';
    const API_EDIT = '/api/agent/edit';
    const API_EDIT_PASSWORD = '/api/agent/edit-password';
    const API_SAVE_GAME_CONFIG = '/api/agent/game-config/save';
    const API_TOGGLE_ENABLED = '/api/agent/toggle-enabled';
    const API_TOGGLE_LOCKED = '/api/agent/toggle-locked';
    const API_GET_GAME_CONFIG = '/api/agent/game-config';
    const API_FIND = '/api/agent/find';
    const API_LIST = '/api/agent/list';
    const API_GET_ROLES = '/api/agent/roles';
    const API_GET_SUB_LIST = '/api/agent/sub/list';
    const API_WALLET_EDIT_MONEY = '/api/agent/wallet/edit-money';
    const API_EDIT_SETTLEMENT = '/api/agent/wallet/edit-settlement';
    const API_TRANSFER_AGENT = '/api/agent/wallet/transfer-agent';
    const API_WALLET_LOG_LIST = '/api/agent/wallet-log/list';
    const API_IP_WHITELIST_ADD = '/api/agent/ip-whitelist/add';
    const API_IP_WHITELIST_REMOVE = '/api/agent/ip-whitelist/remove';
    const API_IP_WHITELIST_ALL = '/api/agent/ip-whitelist/all';

    public function testAddAgent()
    {
        # login
        $this->post(static::API_LOGIN, [
            'account' => 'chloe',
            'password' => 'chloe'
        ]);

        # ---------- error ----------
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'account' => ['The account field is required.'],
                    'password' => ['The password field is required.'],
                    'name' => ['The name field is required.'],
                    'roleId' => ['The role id field is required.'],
                ],
                'message' => 'fail'
            ]);

        $data = [
            'account' => 123,
            'password' => 11111,
            'name' => '',
            'roleId' => 9999,
            'extendId' => 8888,
            'parentId' => 8888,
            'enabled' => -1,
            'locked' => -1
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'account' => ['The account must be a string.'],
                    'password' => ['The password must be a string.'],
                    'name' => ['The name field is required.'],
                    'roleId' => ['The selected role id is invalid.'],
                    'parentId' => ['The selected parent id is invalid.'],
                    'extendId' => ['The selected extend id is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'locked' => ['The selected locked is invalid.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'account' => 'chloe',
            'password' => '11111',
            'name' => 'nnnn',
            'roleId' => 1,
            'extendId' => 2,
            'parentId' => 3,
            'enabled' => 1,
            'locked' => 1
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => '帳號重複'
            ]);

        $data = [
            'account' => 'chloe123',
            'password' => 'chloe',
            'name' => 'ccc',
            'roleId' => 1,
            'extendId' => 2
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => '公司不得有子帳號'
            ]);


        $data = [
            'account' => 'aaa',
            'password' => '11111',
            'name' => 'nnnn',
            'roleId' => 1,
            'parentId' => 2,
            'enabled' => 1,
            'locked' => 1
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => '上層不得為公司'
            ]);

        $data = [
            'account' => 'aaa',
            'password' => '11111',
            'name' => 'nnnn',
            'roleId' => 1,
            'parentId' => 183,
            'enabled' => 1,
            'locked' => 1
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => '不得新增下層'
            ]);

        # ---------- success ----------
        $data = [
            'account' => 'chloeeeeee123',
            'password' => 'chloe',
            'name' => 'ccc',
            'roleId' => 1,
            'extendId' => 6,
            'ipWhitelist' => 1
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

    }

    public function testEditAgent()
    {
        # login
        $this->post(static::API_LOGIN, [
            'account' => 'admin',
            'password' => 'admin'
        ]);

        # ---------- error ----------
        $data = [];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'name' => ['The name field is required.'],
                    'roleId' => ['The role id field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'locked' => ['The locked field is required.'],
                    'ipWhitelist' => ['The ip whitelist field is required.'],
                ],
                'message' => 'fail'
            ]);

        $data = [
            'id' => 'abc',
            'name' => 321,
            'roleId' => 999,
            'enabled' => -1,
            'locked' => -1,
            'ipWhitelist' => 99,
            'feePercent' => 'xxx'
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'name' => ['The name must be a string.'],
                    'roleId' => ['The selected role id is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'locked' => ['The selected locked is invalid.'],
                    'ipWhitelist' => ['The selected ip whitelist is invalid.'],
                    'feePercent' => [
                        'The fee percent may not be greater than 0.',
                        "The fee percent must be a number."
                    ]
                ],
                'message' => 'fail'
            ]);

        # ---------- success ----------
        $data = [
            'id' => 180,
            'name' => 'edit',
            'roleId' => 4,
            'enabled' => 0,
            'locked' => 0,
            'ipWhitelist' => 1
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(200);
    }

    public function testEditPassword()
    {
        # login
        $this->post(static::API_LOGIN, [
            'account' => 'admin',
            'password' => 'admin'
        ]);

        # ---------- error ----------
        $data = [];
        $this->post(static::API_EDIT_PASSWORD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'password' => ['The password field is required.'],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'password' => 'xxx'
        ];
        $this->post(static::API_EDIT_PASSWORD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                ],
                'message' => 'fail'
            ]);

        # ---------- success ----------
        $data = [
            'id' => 181,
            'password' => '123',
        ];
        $this->post(static::API_EDIT_PASSWORD, $data)
            ->assertStatus(200);
    }

    public function testSavePlatformConfig()
    {
        # login
        $this->post(static::API_LOGIN, [
            'account' => 'chloe',
            'password' => 'chloe'
        ]);

        # ---------- error ----------
        $data = [];
        $this->post(static::API_SAVE_GAME_CONFIG, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                ],
                'message' => 'fail'
            ]);

        $data = [
            'id' => 9999,
            'configs' => []
        ];
        $this->post(static::API_SAVE_GAME_CONFIG, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'not found'
            ]);

        $data = [
            'id' => 179,
            'configs' => [
                [
                    'platformId' => 99999,
                    'percent' => 20,
                    'waterPercent' => 20,
                    'bonusPercent' => 20
                ]
            ]
        ];
        $this->post(static::API_SAVE_GAME_CONFIG, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'platformId' => ['The selected platform id is invalid.']
                ],
                'message' => 'fail'
            ]);

        # ---------- success ----------
        $data = [
            'id' => 180,
            'configs' => [
                [
                    'platformId' => 42,
                    'percent' => 20,
                    'waterPercent' => 20,
                    'bonusPercent' => 20
                ],
                [
                    'platformId' => 43,
                    'percent' => 20,
                    'waterPercent' => 20,
                    'bonusPercent' => 20
                ]
            ]
        ];
        $this->post(static::API_SAVE_GAME_CONFIG, $data)
            ->assertStatus(200);

    }

    public function testToggleEnable()
    {
        # login
        $this->post(static::API_LOGIN, [
            'account' => 'chloe',
            'password' => 'chloe'
        ]);

        # ---------- error ----------
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
            'id' => 9999,
            'enabled' => -1
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

        # ---------- success ----------
        $data = [
            'id' => 179,
            'enabled' => 0
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(200);
    }

    public function testToggleLocked()
    {

        # login
        $this->post(static::API_LOGIN, [
            'account' => 'chloe',
            'password' => 'chloe'
        ]);

        # ---------- error ----------
        $data = [];
        $this->post(static::API_TOGGLE_LOCKED, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'locked' => ['The locked field is required.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'id' => 9999,
            'locked' => -1
        ];
        $this->post(static::API_TOGGLE_LOCKED, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'locked' => ['The selected locked is invalid.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'id' => 9999,
            'locked' => 1
        ];
        $this->post(static::API_TOGGLE_LOCKED, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.']
                ],
                'message' => 'fail'
            ]);

        # ---------- success ----------
        $data = [
            'id' => 179,
            'locked' => 0
        ];
        $this->post(static::API_TOGGLE_LOCKED, $data)
            ->assertStatus(200);
    }

    public function testGetPlatformConfig()
    {

        # login
        $this->post(static::API_LOGIN, [
            'account' => 'chloe',
            'password' => 'chloe'
        ]);

        # ---------- error ----------
        $id = [];
        $this->call('GET', static::API_GET_GAME_CONFIG, $id)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'not found'
            ]);

        $id = ['id' => 220];
        $this->call('GET', static::API_GET_GAME_CONFIG, $id)
            ->assertExactJson([
                'message' => '查無上層資料'
            ]);

        # 重新登入'非公司層級'的帳號
        $this->call('GET', static::API_LOGOUT, []);
        $this->post(static::API_LOGIN, [
            'account' => 'larry01',
            'password' => 'ivan'
        ])->assertStatus(200);

        $id = ['id' => 2];
        $this->call('GET', static::API_GET_GAME_CONFIG, $id)
            ->assertStatus(403)
            ->assertExactJson([
                'message' => '無權限'
            ]);

        # ---------- success ----------
        # 登入'公司層級'的帳號
        $this->call('GET', static::API_LOGOUT, []);
        $this->post(static::API_LOGIN, [
            'account' => 'chloe',
            'password' => 'chloe'
        ])->assertStatus(200);

        $id = ['id' => 179];
        $this->call('GET', static::API_GET_GAME_CONFIG, $id)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'platformId',
                        'type',
                        'key',
                        'name',
                        'exists',
                        'enabled',
                        'percent',
                        'waterPercent',
                        'bonusPercent',
                        'maxWaterPercent',
                        'maxBonusPercent'
                    ]

                ]
            ]);
    }

    public function testGetAgent()
    {
        # login
        $this->post(static::API_LOGIN, [
            'account' => 'chloe',
            'password' => 'chloe'
        ]);

        # ---------- error ----------
        $data = [];
        $this->call('GET', static::API_FIND, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'not found'
            ]);

        $data = ['id' => 999];
        $this->call('GET', static::API_FIND, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'not found'
            ]);
        $data = ['id' => 3];
        $this->call('GET', static::API_FIND, $data)
            ->assertStatus(403)
            ->assertExactJson([
                'message' => 'agent.agent-cannot-edit'
            ]);

        # ---------- success ----------
        $data = ['id' => 179];
        $this->call('GET', static::API_FIND, $data)
            ->assertStatus(200);
    }

    public function testGetAgentList()
    {
        # login
        $this->post(static::API_LOGIN, [
            'account' => 'chloe',
            'password' => 'chloe'
        ]);

        # ---------- error ----------
        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'level' => ['The level field is required.'],
                ],
                'message' => 'fail'
            ]);


        # 重新登入'非公司層級'的帳號
        $this->call('GET', static::API_LOGOUT, []);
        $this->post(static::API_LOGIN, [
            'account' => 'larry01',
            'password' => 'ivan'
        ])->assertStatus(200);
        $data = [
            'level' => 0,
            'parentId' => 999
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'level' => ['The selected level is invalid.'],
                    'parentId' => ['The selected parent id is invalid.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'level' => 2,
            'parentId' => 999
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'parentId' => ['The selected parent id is invalid.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'level' => 999,
            'parentId' => 999
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'level' => ['The selected level is invalid.'],
                    'parentId' => ['The selected parent id is invalid.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'level' => 3,
            'parentId' => 999,
            'name' => 'xxxxx',
            'account' => 'xxxxxxx',
            'enabled' => 99,
            'locked' => 99,
            'page' => 'xxx',
            'perPage' => 999
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'parentId' => ['The selected parent id is invalid.'],
                    'enabled' => ['The selected enabled is invalid.'],
                    'locked' => ['The selected locked is invalid.'],
                    'page' => ['The page must be a number.']
                ],
                'message' => 'fail'
            ]);

        # ---------- success ----------
        # 登入'公司層級'的帳號
        $this->call('GET', static::API_LOGOUT, []);
        $this->post(static::API_LOGIN, [
            'account' => 'chloe',
            'password' => 'chloe'
        ])->assertStatus(200);

        $data = [
            'level' => 2,
            'parentId' => 3,
            'name' => 'xxxxx',
            'account' => 'xxxxxxx',
            'enabled' => 1,
            'locked' => 1,
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        $data = [
            'level' => 3,
            'enabled' => '1',
            'locked' => 0
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'content' => [
                        [
                            'id',
                            'account',
                            'name',
                            'level',
                            'roleId',
                            'roleName',
                            'numSubAccounts',
                            'enabled',
                            'locked',
                            'settlementAmount',
                            'money',
                            'code',
                            'updatedAt'
                        ]
                    ]
                ]
            ]);

    }

    public function testGetRoles()
    {
        # login
        $this->post(static::API_LOGIN, [
            'account' => 'chloe',
            'password' => 'chloe'
        ]);

        # ---------- success ----------
        $this->call('GET', static::API_GET_ROLES)
            ->assertStatus(200);

        # 重新登入其他帳號
        $this->call('GET', static::API_LOGOUT, []);
        $this->post(static::API_LOGIN, [
            'account' => 'larry01',
            'password' => 'ivan'
        ])->assertStatus(200);

        $this->call('GET', static::API_GET_ROLES)
            ->assertStatus(200);
    }

    public function testGetSubList()
    {
        # login
        $this->post(static::API_LOGIN, [
            'account' => 'chloe',
            'password' => 'chloe'
        ]);

        # ---------- success ----------
        $data = [];
        $this->call('GET', static::API_GET_SUB_LIST)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'content' => []
                ]
            ]);

    }

    public function testEditWalletMoney()
    {
        # login
        $this->post(static::API_LOGIN, [
            'account' => 'chloe',
            'password' => 'chloe'
        ]);

        # ---------- error ----------
        $data = [];
        $this->post(static::API_WALLET_EDIT_MONEY, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'amount' => ['The amount field is required.']
                ],
                'message' => 'fail'
            ]);

        # 改停用
        $dataEnabled = [
            'id' => 181,
            'enabled' => 0
        ];
        $this->post(static::API_TOGGLE_ENABLED, $dataEnabled)
            ->assertStatus(200);
        $data = [
            'id' => 179,
            'locked' => 0
        ];
        $this->post(static::API_TOGGLE_LOCKED, $data)
            ->assertStatus(200);
        # test
        $data = [
            'id' => 181,
            'amount' => 10
        ];
        $this->post(static::API_WALLET_EDIT_MONEY, $data)
            ->assertStatus(400)
            ->assertExactJson(['message' => '代理被鎖定 or 停用中']);

        # 登入非公司帳號
        $this->call('GET', static::API_LOGOUT, []);
        $this->post(static::API_LOGIN, [
            'account' => 'larry01',
            'password' => 'ivan'
        ])->assertStatus(200);
        # test
        $data = [
            'id' => 1,
            'amount' => 10
        ];
        $this->post(static::API_WALLET_EDIT_MONEY, $data)
            ->assertStatus(500)
            ->assertExactJson([
                'message' => 'agent is company'
            ]);

        # ---------- success ----------
        # 啟用
        $dataEnabled = [
            'id' => 181,
            'enabled' => 1
        ];
        $this->post(static::API_TOGGLE_ENABLED, $dataEnabled)
            ->assertStatus(200);
        # 不鎖定
        $dataLocked = [
            'id' => 181,
            'locked' => 0
        ];
        $this->post(static::API_TOGGLE_LOCKED, $dataLocked)
            ->assertStatus(200);

        # 登入非公司帳號
        $this->call('GET', static::API_LOGOUT, []);
        $this->post(static::API_LOGIN, [
            'account' => 'larry01',
            'password' => 'ivan'
        ])->assertStatus(200);

        # test
        $data = [
            'id' => 181,
            'amount' => 100000
        ];
        $this->post(static::API_WALLET_EDIT_MONEY, $data)
            ->assertStatus(200);

    }

    public function testEditWalletSettlement()
    {
        # login
        $this->post(static::API_LOGIN, [
            'account' => 'chloe',
            'password' => 'chloe'
        ]);

        # ---------- error ----------
        $data = [];
        $this->post(static::API_EDIT_SETTLEMENT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'amount' => ['The amount field is required.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'id' => 1,
            'amount' => 10
        ];
        $this->post(static::API_EDIT_SETTLEMENT, $data)
            ->assertStatus(500)
            ->assertExactJson([
                'message' => 'agent is company'
            ]);

        # ---------- success ----------
        # 登入非公司帳號
        $id = 181;
        $agent = Agent::findOrError($id);
        $agent->enabled = 1;
        $agent->locked = 0;
        $agent->saveOrError();
        $data = [
            'id' => $id,
            'amount' => 100000
        ];
        $this->post(static::API_EDIT_SETTLEMENT, $data)
            ->assertStatus(200);
    }

    public function testTransferWalletToAgent()
    {
        # login
        $this->post(static::API_LOGIN, [
            'account' => 'chloe',
            'password' => 'chloe'
        ]);

        # ---------- error ----------
        $data = [];
        $this->post(static::API_TRANSFER_AGENT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'fromId' => ['The from id field is required.'],
                    'toId' => ['The to id field is required.'],
                    'amount' => ['The amount field is required.']
                ],
                'message' => 'fail'
            ]);

        # 傳入的 fromId 為子帳號
        $data = [
            'fromId' => 187,
            'toId' => 188,
            'amount' => 10
        ];
        $this->post(static::API_TRANSFER_AGENT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'fromId' => ['The selected from id is invalid.']
                ],
                'message' => 'fail'
            ]);

        # 傳入的 toId 為子帳號
        $data = [
            'fromId' => 188,
            'toId' => 187,
            'amount' => 10
        ];
        $this->post(static::API_TRANSFER_AGENT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'toId' => ['The selected to id is invalid.']
                ],
                'message' => 'fail'
            ]);

        # formId, toId 為子帳號
        $data = [
            'fromId' => 187,
            'toId' => 217,
            'amount' => 10
        ];
        $this->post(static::API_TRANSFER_AGENT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'fromId' => ['The selected from id is invalid.'],
                    'toId' => ['The selected to id is invalid.']
                ],
                'message' => 'fail'
            ]);

        # fromId 為公司
        $data = [
            'fromId' => 1,
            'toId' => 207,
            'amount' => 10
        ];
        $this->post(static::API_TRANSFER_AGENT, $data)
            ->assertStatus(500)
            ->assertExactJson([
                'message' => 'agent is company'
            ]);

        # toId 為公司
        $data = [
            'fromId' => 207,
            'toId' => 1,
            'amount' => 10
        ];
        $this->post(static::API_TRANSFER_AGENT, $data)
            ->assertStatus(500)
            ->assertExactJson([
                'message' => 'agent is company'
            ]);

        # formId, toId 為公司
        $data = [
            'fromId' => 1,
            'toId' => 2,
            'amount' => 10
        ];
        $this->post(static::API_TRANSFER_AGENT, $data)
            ->assertStatus(500)
            ->assertExactJson([
                'message' => 'agent is company'
            ]);

        # formId & toId 非上下直屬關係
        $fromId = 207;
        $toId = 208;
        $agWallet = AgentWallet::findOrError($fromId);
        $agWallet->money = 100;
        $agWallet->saveOrError();
        $data = [
            'fromId' => $fromId,
            'toId' => $toId,
            'amount' => 10
        ];
        $this->post(static::API_TRANSFER_AGENT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => '必需為直屬上、下關係'
            ]);

        # formId 額度不足
        $fromId = 180;
        $toId = 181;
        $agWallet = AgentWallet::findOrError($fromId);
        $agWallet->money = 0;
        $agWallet->saveOrError();
        $data = [
            'fromId' => $fromId,
            'toId' => $toId,
            'amount' => 10
        ];

        $this->post(static::API_TRANSFER_AGENT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => '額度不足'
            ]);

        # fromAgent is locked and disabled
        $fromId = 180;
        $toId = 181;
        $fromAgent = Agent::findOrError($fromId);
        $fromAgent->enabled = 0;
        $fromAgent->locked = 1;
        $fromAgent->saveOrError();
        $toAgent = Agent::findOrError($toId);
        $toAgent->enabled = 1;
        $toAgent->locked = 0;
        $toAgent->saveOrError();

        $agWallet = AgentWallet::findOrError($fromId);
        $agWallet->money = 100;
        $agWallet->saveOrError();

        $data = [
            'fromId' => $fromId,
            'toId' => $toId,
            'amount' => 10
        ];
        $this->post(static::API_TRANSFER_AGENT, $data)
            ->assertStatus(403)
            ->assertExactJson([
                'message' => 'payer agent is locked or disabled'
            ]);

        # toAgent is locked and disabled
        $fromId = 180;
        $toId = 181;
        $fromAgent = Agent::findOrError($fromId);
        $fromAgent->enabled = 1;
        $fromAgent->locked = 0;
        $fromAgent->saveOrError();
        $toAgent = Agent::findOrError($toId);
        $toAgent->enabled = 0;
        $toAgent->locked = 1;
        $toAgent->saveOrError();

        $agWallet = AgentWallet::findOrError($fromId);
        $agWallet->money = 100;
        $agWallet->saveOrError();

        $data = [
            'fromId' => $fromId,
            'toId' => $toId,
            'amount' => 10
        ];
        $this->post(static::API_TRANSFER_AGENT, $data)
            ->assertStatus(403)
            ->assertExactJson([
                'message' => 'payee agent is locked or disabled'
            ]);

        # ---------- success ----------
        $fromId = 180;
        $toId = 181;
        $fromAgent = Agent::findOrError($fromId);
        $fromAgent->enabled = 1;
        $fromAgent->locked = 0;
        $fromAgent->saveOrError();
        $toAgent = Agent::findOrError($toId);
        $toAgent->enabled = 1;
        $toAgent->locked = 0;
        $toAgent->saveOrError();

        $agWallet = AgentWallet::findOrError($fromId);
        $agWallet->money = 100;
        $agWallet->saveOrError();

        $data = [
            'fromId' => $fromId,
            'toId' => $toId,
            'amount' => 10
        ];
        $this->post(static::API_TRANSFER_AGENT, $data)
            ->assertStatus(200);
    }

    public function testGetWalletLogList()
    {

        # ---------- error ----------
        $data = [];
        $this->call('GET', static::API_WALLET_LOG_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.']
                ],
                'message' => 'fail'
            ]);

        # ---------- success ----------
        $data = [
            'id' => 5
        ];
        $this->call('GET', static::API_WALLET_LOG_LIST, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'content' => []
                ]
            ]);

        $data = [
            'id' => 5,
            'type' => 'money-to-settlement',
            'startTime' => '2018-08-09 09:55:36',
            'endTime' => '2018-09-09 09:55:36',
            'sorts' => ['id, desc', 'type, asc'],
            'page' => 2,
            'perPage' => 3
        ];
        $this->call('GET', static::API_WALLET_LOG_LIST, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'content' => []
                ]
            ]);
    }

    public function testIpWhitelistAdd()
    {

        # ---------- error ----------
        $data = [];
        $this->post(static::API_IP_WHITELIST_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'ip' => ['The ip field is required.']
                ],
                'message' => 'fail'
            ]);

        # ---------- success ----------
        $id = 179;
        $ip = '127.0.0.1';
        $data = [
            'id' => $id,
            'ip' => $ip
        ];
        $this->post(static::API_IP_WHITELIST_ADD, $data)
            ->assertStatus(200);

        #check
        $agentIpWhiteList = AgentIpWhitelist::where('agent_id', $id)->first();
        $this->assertEquals($agentIpWhiteList->ip, $ip);

    }

    public function testIpWhiteListRemvoe()
    {
        # ---------- error ----------
        $data = [];
        $this->post(static::API_IP_WHITELIST_REMOVE, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'id' => 9999
        ];
        $this->post(static::API_IP_WHITELIST_REMOVE, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.']
                ],
                'message' => 'fail'
            ]);

        # ---------- success ----------
        # 新增測試資料
        $id = 179;
        $ip = '127.0.0.1';
        $agIpWhiteList = new AgentIpWhitelist();
        $agIpWhiteList->agent_id = $id;
        $agIpWhiteList->ip = $ip;
        $agIpWhiteList->saveOrError();

        $agIpWhiteList = $agIpWhiteList->fresh();

        # remvoe
        $data = [
            'id' => $agIpWhiteList->id
        ];
        $this->post(static::API_IP_WHITELIST_REMOVE, $data)
            ->assertStatus(200);

        #check
        $agIpWhiteList = $agIpWhiteList->fresh();
        $checkItem = AgentIpWhitelist::where('id', $data['id'])->get();
        $this->assertCount(0, $checkItem);

    }

    public function testIpWhiteListAll()
    {
        # ---------- error ----------
        $data = [];
        $this->call('GET', static::API_IP_WHITELIST_ALL, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.']
                ],
                'message' => 'fail'
            ]);

        # ---------- success ----------
        # 新增測試資料
        $id = 1;
        $agentIpWhitelist = new AgentIpWhitelist();
        $agentIpWhitelist->agent_id = $id;
        $agentIpWhitelist->ip = '127.0.0.1';
        $agentIpWhitelist->saveOrError();
        $agentIpWhitelist->fresh();

        $data = [
            'id' => $id
        ];
        $this->call('GET', static::API_IP_WHITELIST_ALL, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'ip'
                    ]
                ]
            ]);
    }
}
