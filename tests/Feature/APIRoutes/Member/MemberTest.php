<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Member;
use App\Models\Agent;
use App\Models\LogMemberWallet;
use App\Models\LogMemberTransfer;
use App\Models\ClubRank;
use App\Models\AgentPlatformConfig;
use App\Models\MemberPlatformActive;

class MemberTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_LOGIN = '/api/public/login';
    const API_TOGGLE_ENABLED = '/api/member/toggle-enabled';
    const API_TOGGLE_LOCKED = '/api/member/toggle-locked';
    const API_ADD_TAG = '/api/member/add-tag';
    const API_REMOVE_TAG = '/api/member/remove-tag';
    const API_CLUB_RANK = '/api/member/club-rank';
    const API_TAGS = '/api/member/tags';
    const API_CLUBS = '/api/member/clubs';
    const API_CLUB_RANKS = '/api/member/club-ranks';
    const API_LIST = '/api/member/list';
    const API_MEMBER_ADD = '/api/member/add';
    const API_MEMBER_EDIT = '/api/member/edit';
    const API_MEMBER_EDIT_PWD = '/api/member/edit-password';

    # 新增
    const API_ADD = '/api/member/add';
    const API_EDIT = '/api/member/edit';
    const API_EDIT_PASSWORD = '/api/member/edit-password';
    const API_EDIT_MONEY = '/api/member/wallet/edit-money';
    const API_EDIT_BONUS = '/api/member/wallet/edit-bonus';
    const API_GIVE_MONEY = '/api/member/wallet/give-money';
    const API_TAKE_BACK = '/api/member/wallet/take-back';
    const API_TRANSFER_GAME = '/api/member/wallet/transfer-game';
    const API_TRANSFER_WALLET = '/api/member/wallet/transfer-wallet';
    const API_EDIT_GAME = '/api/member/wallet/edit-game';
    const API_PLATFORM_OPTIONS = 'api/member/platform-options';
    const API_PLATFORM_WALLET = '/api/member/platform-wallet';

    # 10/01 新增
    const API_PLATFORM_TOGGLE_ENABLED = '/api/member/platform/toggle-enabled';
    const API_RELATED_AGENTS = '/api/member/related-agents';
    const API_WALLET_LOGS = '/api/member/wallet/logs';
    const API_TRANSFER_LOGS = '/api/member/transfer-logs';

    public function testError()
    {
        # toggle-enabled
        $data = [];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'enabled' => ['The enabled field is required.'],
                    'id' => ['The id field is required.']
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

        # toggle-locked
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
            'id' => 'xxx',
            'locked' => 'xxx'
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

        # add-tag
        $data = [];
        $this->post(static::API_ADD_TAG, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'tagId' => ['The tag id field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'tagId' => 'xxx'
        ];
        $this->post(static::API_ADD_TAG, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'tagId' => ['The selected tag id is invalid.']
                ],
                'message' => 'fail'
            ]);

        # remove-tag
        $data = [];
        $this->post(static::API_REMOVE_TAG, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'tagId' => ['The tag id field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'tagId' => 'xxx'
        ];
        $this->post(static::API_REMOVE_TAG, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'tagId' => ['The selected tag id is invalid.']
                ],
                'message' => 'fail'
            ]);

        # club-rank
        $data = [];
        $this->post(static::API_CLUB_RANK, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'clubRankId' => ['The club rank id field is required.']
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'clubRankId' => 'xxx'
        ];
        $this->post(static::API_CLUB_RANK, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'clubRankId' => ['The selected club rank id is invalid.']
                ],
                'message' => 'fail'
            ]);

        # club-rank
        $data = [
            'id' => 1,
            'clubRankId' => 3
        ];
        $this->post(static::API_CLUB_RANK, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'forbid club_rank_id'
            ]));

        # list
        $data = [
            'account' => 'xxx',
            'name' => 'xxx',
            'enabled' => 'xxx',
            'locked' => 'xxx',
            'clubId' => 'xxx',
            'clubRankId' => 'xxx',
            'tagId' => 'xxx',
            'page' => 'xxx',
            'perPage' => 'xxx',
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'enabled' => ['The selected enabled is invalid.'],
                    'locked' => ['The selected locked is invalid.'],
                    'clubId' => ['The club id must be an integer.'],
                    'clubRankId' => ['The club rank id must be an integer.'],
                    'tagId' => ['The tag id must be an integer.'],
                    'page' => ['The page must be an integer.'],
                    'perPage' => ['The per page must be an integer.'],
                ],
                'message' => 'fail'
            ]);

        # member add
        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "account" => ["The account field is required."],
                    "agentId" => ["The agent id field is required."],
                    "clubRankId" => ["The club rank id field is required."],
                    "enabled" => ["The enabled field is required."],
                    "locked" => ["The locked field is required."],
                    "name" => ["The name field is required."],
                    "nickname" => ["The nickname field is required."],
                    "password" => ["The password field is required."],
                    "phone" => ["The phone field is required."],
                    "gender" => ["The gender field is required."],
                ],
                'message' => 'fail'
            ]);
        $data = [
            'agentId' => 'xxx',
            'account' => 4654,
            'password' => 123,
            'phone' => 123,
            'clubRankId' => 'xxx',
            'name' => 111,
            'nickName' => 111,
            'birth' => 111,
            'gender' => 111,
            'email' => 111,
            'qq' => 111,
            'wechat' => 111,
            'weibo' => 111,
            'enabled' => 111,
            'locked' => 111
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "account" => ["The account must be a string."],
                    "agentId" => ["The selected agent id is invalid."],
                    "birth" => ["The birth is not a valid date."],
                    "clubRankId" => ["The selected club rank id is invalid."],
                    "email" => ["The email must be a valid email address."],
                    "enabled" => ["The selected enabled is invalid."],
                    "locked" => ["The selected locked is invalid."],
                    "name" => ["The name must be a string."],
                    "nickname" => ["The nickname field is required."],
                    "password" => ["The password must be a string.", "The password must be at least 6 characters."],
                    "phone" => ["The phone must be a string."],
                    "qq" => ["The qq must be a string."],
                    "wechat" => ["The wechat must be a string."],
                    "weibo" => ["The weibo must be a string."],
                    'gender' => ['The selected gender is invalid.']
                ],
                'message' => 'fail'
            ]);

        # member edit
        $data = [];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "clubRankId" => ["The club rank id field is required."],
                    "enabled" => ["The enabled field is required."],
                    "gender" => ["The gender field is required."],
                    "id" => ["The id field is required."],
                    "locked" => ["The locked field is required."],
                    "name" => ["The name field is required."],
                    "nickname" => ["The nickname field is required."],
                    "phone" => ["The phone field is required."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 111,
            'phone' => 123,
            'clubRankId' => 'xxx',
            'name' => 111,
            'nickName' => 111,
            'birth' => 111,
            'gander' => 111,
            'email' => 111,
            'qq' => 111,
            'wechat' => 111,
            'weibo' => 111,
            'enabled' => 111,
            'locked' => 111
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "birth" => ["The birth is not a valid date."],
                    "clubRankId" => ["The selected club rank id is invalid."],
                    "email" => ["The email must be a string."],
                    "enabled" => ["The selected enabled is invalid."],
                    "gender" => ["The gender field is required."],
                    "id" => ["The selected id is invalid."],
                    "locked" => ["The selected locked is invalid."],
                    "name" => ["The name must be a string."],
                    "nickname" => ["The nickname field is required."],
                    "phone" => ["The phone must be a string."],
                    "qq" => ["The qq must be a string."],
                    "wechat" => ["The wechat must be a string."],
                    "weibo" => ["The weibo must be a string."]
                ],
                'message' => 'fail'
            ]);

        # edit password
        $data = [];
        $this->post(static::API_EDIT_PASSWORD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The id field is required.'],
                    'password' => ['The password field is required.']
                ],
                'message' => 'fail'
            ]);
        $memberId = Member::first()->id;
        $data = [
            'id' => $memberId,
            'password' => 111
        ];
        $this->post(static::API_EDIT_PASSWORD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "password" => [
                        "The password must be a string.",
                        "The password must be at least 6 characters."
                    ]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => $memberId,
            'password' => 111
        ];
        $this->post(static::API_EDIT_PASSWORD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "password" => [
                        "The password must be a string.",
                        "The password must be at least 6 characters."
                    ]
                ],
                'message' => 'fail'
            ]);

        # edit money
        $data = [];
        $this->post(static::API_EDIT_MONEY, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "amount" => ["The amount field is required."],
                    "id" => ["The id field is required."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'amount' => 'xxx',
            'remark' => 111
        ];
        $this->post(static::API_EDIT_MONEY, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    "amount" => ["The amount must be a number."],
                    "remark" => ["The remark must be a string."]
                ],
                'message' => 'fail'
            ]);

        # edit bonus
        $data = [];
        $this->post(static::API_EDIT_BONUS, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "amount" => ["The amount field is required."],
                    "id" => ["The id field is required."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'id' => 'xxx',
            'amount' => 'xxx',
            'remark' => 111
        ];
        $this->post(static::API_EDIT_BONUS, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    "amount" => ["The amount must be a number."],
                    "remark" => ["The remark must be a string."]
                ],
                'message' => 'fail'
            ]);

        # give money
        $data = [];
        $this->post(static::API_GIVE_MONEY, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "agentId" => ["The agent id field is required."],
                    "amount" => ["The amount field is required."],
                    "memberId" => ["The member id field is required."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'agentId' => 'xxx',
            'memberId' => 'xxx',
            'amount' => 'xxx',
            'remark' => 111
        ];
        $this->post(static::API_GIVE_MONEY, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "agentId" => ["The selected agent id is invalid."],
                    "amount" => ["The amount must be an integer."],
                    "memberId" => ["The selected member id is invalid."],
                    "remark" => ["The remark must be a string."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'agentId' => 'xxx',
            'memberId' => 'xxx',
            'amount' => -1,
        ];
        $this->post(static::API_GIVE_MONEY, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "agentId" => ["The selected agent id is invalid."],
                    "amount" => ["The amount must be at least 0."],
                    "memberId" => ["The selected member id is invalid."],
                ],
                'message' => 'fail'
            ]);

        # take back
        $data = [];
        $this->post(static::API_TAKE_BACK, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "agentId" => ["The agent id field is required."],
                    "amount" => ["The amount field is required."],
                    "memberId" => ["The member id field is required."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'agentId' => 'xxx',
            'memberId' => 'xxx',
            'amount' => 'xxx',
            'remark' => 111
        ];
        $this->post(static::API_TAKE_BACK, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "agentId" => ["The selected agent id is invalid."],
                    "amount" => ["The amount must be an integer."],
                    "memberId" => ["The selected member id is invalid."],
                    "remark" => ["The remark must be a string."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'agentId' => 'xxx',
            'memberId' => 'xxx',
            'amount' => -1,
        ];
        $this->post(static::API_TAKE_BACK, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "agentId" => ["The selected agent id is invalid."],
                    "amount" => ["The amount must be at least 0."],
                    "memberId" => ["The selected member id is invalid."],
                ],
                'message' => 'fail'
            ]);

        # transfer game
        $data = [];
        $this->post(static::API_TRANSFER_GAME, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "platformId" => ["The platform id field is required."],
                    "amount" => ["The amount field is required."],
                    "memberId" => ["The member id field is required."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'platformId' => 'xxx',
            'memberId' => 'xxx',
            'amount' => 'xxx',
            'remark' => 111
        ];
        $this->post(static::API_TRANSFER_GAME, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "platformId" => ["The selected platform id is invalid."],
                    "amount" => ["The amount must be an integer."],
                    "memberId" => ["The selected member id is invalid."],
                    "remark" => ["The remark must be a string."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'platformId' => 'xxx',
            'memberId' => 'xxx',
            'amount' => -1,
        ];
        $this->post(static::API_TRANSFER_GAME, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "platformId" => ["The selected platform id is invalid."],
                    "amount" => ["The amount must be at least 0."],
                    "memberId" => ["The selected member id is invalid."],
                ],
                'message' => 'fail'
            ]);

        # transfer wallet
        $data = [];
        $this->post(static::API_TRANSFER_WALLET, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "platformId" => ["The platform id field is required."],
                    "amount" => ["The amount field is required."],
                    "memberId" => ["The member id field is required."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'platformId' => 'xxx',
            'memberId' => 'xxx',
            'amount' => 'xxx',
            'remark' => 111
        ];
        $this->post(static::API_TRANSFER_WALLET, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "platformId" => ["The selected platform id is invalid."],
                    "amount" => ["The amount must be an integer."],
                    "memberId" => ["The selected member id is invalid."],
                    "remark" => ["The remark must be a string."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'platformId' => 'xxx',
            'memberId' => 'xxx',
            'amount' => -1,
        ];
        $this->post(static::API_TRANSFER_WALLET, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "platformId" => ["The selected platform id is invalid."],
                    "amount" => ["The amount must be at least 0."],
                    "memberId" => ["The selected member id is invalid."],
                ],
                'message' => 'fail'
            ]);

        # edit game
        $data = [];
        $this->post(static::API_EDIT_GAME, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "platformId" => ["The platform id field is required."],
                    "amount" => ["The amount field is required."],
                    "memberId" => ["The member id field is required."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'platformId' => 'xxx',
            'memberId' => 'xxx',
            'amount' => 'xxx',
            'remark' => 111
        ];
        $this->post(static::API_EDIT_GAME, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "platformId" => ["The selected platform id is invalid."],
                    "amount" => ["The amount must be a number."],
                    "memberId" => ["The selected member id is invalid."],
                    "remark" => ["The remark must be a string."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'platformId' => 'xxx',
            'memberId' => 'xxx',
            'amount' => -1,
        ];
        $this->post(static::API_EDIT_GAME, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "platformId" => ["The selected platform id is invalid."],
                    "memberId" => ["The selected member id is invalid."],
                ],
                'message' => 'fail'
            ]);

        # platform wallet
        $data = [];
        $this->call('GET', static::API_PLATFORM_WALLET, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "platformId" => ["The platform id field is required."],
                    "memberId" => ["The member id field is required."]
                ],
                'message' => 'fail'
            ]);
        $data = [
            'memberId' => 'xxx',
            'platformId' => 'xxx'
        ];
        $this->call('GET', static::API_PLATFORM_WALLET, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    "platformId" => ["The selected platform id is invalid."],
                    "memberId" => ["The selected member id is invalid."]
                ],
                'message' => 'fail'
            ]);

        # platform toggle-enabled
        $data = [];
        $this->post(static::API_PLATFORM_TOGGLE_ENABLED, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'fail',
                'errors' => [
                    'memberId' => ['The member id field is required.'],
                    'platformId' => ['The platform id field is required.'],
                    'enabled' => ['The enabled field is required.']
                ],

            ]);

        $data = [
            'memberId' => 999999999,
            'platformId' => '$#%**&',
            'enabled' => 88
        ];
        $this->post(static::API_PLATFORM_TOGGLE_ENABLED, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'fail',
                'errors' => [
                    'memberId' => ['The selected member id is invalid.'],
                    'platformId' => ['The selected platform id is invalid.'],
                    'enabled' => ['The selected enabled is invalid.']
                ]
            ]);

        # wallet logs
        $data = [];
        $this->call('GET', static::API_WALLET_LOGS, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'fail',
                'errors' => [
                    'memberId' => ['The member id field is required.'],
                    'startTime' => ['The start time field is required.'],
                    'endTime' => ['The end time field is required.']
                ]
            ]);

        $data = [
            'memberId' => 9999999,
            'startTime' => '%$%#%$',
            'endTime' => '2020-01-01',
            'type' => 'uuuuu',
            'sorts' => 'type,asc',
            'page' => 'aaa',
            'perPage' => 'ppp'
        ];
        $this->call('GET', static::API_WALLET_LOGS, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'fail',
                'errors' => [
                    'memberId' => ['The selected member id is invalid.'],
                    'startTime' => ['The start time does not match the format Y-m-d H:i:s.'],
                    'endTime' => ['The end time does not match the format Y-m-d H:i:s.'],
                    'sorts' => ['sorts not array or value error']
                ]
            ]);

        # transfer logs
        $data = [];
        $this->call('GET', static::API_TRANSFER_LOGS, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'fail',
                'errors' => [
                    'memberId' => ['The member id field is required.'],
                    'platformId' => ['The platform id field is required.'],
                    'startTime' => ['The start time field is required.'],
                    'endTime' => ['The end time field is required.']
                ]
            ]);

        $data = [
            'memberId' => 9999999,
            'platformId' => 0,
            'startTime' => 'aaa',
            'endTime' => 0,
            'sorts' => 123,
            'page' => 'ppp',
            'perPage' => 77777
        ];
        $this->call('GET', static::API_TRANSFER_LOGS, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'message' => 'fail',
                'errors' => [
                    'memberId' => ['The selected member id is invalid.'],
                    'platformId' => ['The selected platform id is invalid.'],
                    'startTime' => ['The start time does not match the format Y-m-d H:i:s.'],
                    'endTime' => ['The end time does not match the format Y-m-d H:i:s.'],
                    'sorts' => ['sorts not array or value error']
                ]
            ]);

    }

    public function testSuccess()
    {
        # toggle-enabled
        $data = [
            'id' => 1,
            'enabled' => 1
        ];
        $this->post(static::API_TOGGLE_ENABLED, $data)
            ->assertStatus(200);

        # toggle-locked
        $data = [
            'id' => 1,
            'locked' => 1
        ];
        $this->post(static::API_TOGGLE_LOCKED, $data)
            ->assertStatus(200);

        # add-tag
        $data = [
            'id' => 1,
            'tagId' => 1
        ];
        $this->post(static::API_ADD_TAG, $data)
            ->assertStatus(200);

        # remove-tag
        $data = [
            'id' => 1,
            'tagId' => 1
        ];
        $this->post(static::API_REMOVE_TAG, $data)
            ->assertStatus(200);

        # club-rank
        $data = [
            'id' => 1,
            'clubRankId' => 117
        ];
        $this->post(static::API_CLUB_RANK, $data)
            ->assertStatus(200);

        # tags
        $this->call('GET', static::API_TAGS)
            ->assertStatus(200);

        # clubs
        $this->call('GET', static::API_CLUBS)
            ->assertStatus(200);

        # club-ranks
        $data = ['id' => 1];
        $this->call('GET', static::API_CLUB_RANKS, $data)
            ->assertStatus(200);

        # list

        # agent login
        $this->post(static::API_LOGIN, ['account' => 'chloe', 'password' => 'chloe'])->assertStatus(200);
        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);
        $data = [
            'account' => 'xxx',
            'name' => 'xxx',
            'enabled' => 1,
            'locked' => 1,
            'clubId' => 1,
            'clubRankId' => 1,
            'tagId' => 1,
            'page' => 1,
            'perPage' => 1,
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(200);

        # member add
        $lv5AgentId = Agent::where('level', 5)
            ->first()
            ->id;
        $clubRankId = ClubRank::first()->id;
        $data = [
            'agentId' => $lv5AgentId,
            'account' => 'xxxxxx',
            'password' => 'xxxxxx',
            'phone' => '654321324',
            'clubRankId' => $clubRankId,
            'name' => 'xxx',
            'nickname' => 'xxx',
            'gender' => 'M',
            'enabled' => 1,
            'locked' => 1,
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(200);

        # member edit
        $memberId = Member::max('id');
        $data = [
            'id' => $memberId,
            'phone' => '654321324',
            'clubRankId' => $clubRankId,
            'name' => 'xxx',
            'nickname' => 'xxx',
            'gender' => 'M',
            'enabled' => 1,
            'locked' => 1,
        ];
        $this->post(static::API_EDIT, $data)
            ->assertStatus(200);

        # edit password
        $data = [
            'id' => $memberId,
            'password' => 'xxxxxx',
        ];
        $this->post(static::API_EDIT_PASSWORD, $data)
            ->assertStatus(200);

        # edit money
        $data = [
            'id' => 80,
            'amount' => 1,
        ];
        $this->post(static::API_EDIT_MONEY, $data)
            ->assertStatus(200);

        # edit bonus
        $data = [
            'id' => 80,
            'amount' => 1,
        ];
        $this->post(static::API_EDIT_BONUS, $data)
            ->assertStatus(200);

        # give money
        $data = [
            'agentId' => $lv5AgentId,
            'memberId' => 80,
            'amount' => 1,
        ];
        $this->post(static::API_GIVE_MONEY, $data)
            ->assertStatus(200);

        # take back
        $data = [
            'agentId' => $lv5AgentId,
            'memberId' => 80,
            'amount' => 1,
        ];
        $this->post(static::API_TAKE_BACK, $data)
            ->assertStatus(200);

        # transfer game
        $platformId = AgentPlatformConfig::where('agent_id', user()->model()->id)
            ->where('enabled', 1)
            ->first()
            ->platform_id;
        $memberId = MemberPlatformActive::where('platform_id', $platformId)
            ->where('active_status', MemberPlatformActive::ACTIVE_STATUS_COMPLETED)
            ->first()
            ->member_id;
        $data = [
            'platformId' => $platformId,
            'memberId' => $memberId,
            'amount' => 1
        ];
        $this->post(static::API_TRANSFER_GAME, $data)
            ->assertStatus(200);

        # transfer wallet
        $data = [
            'platformId' => $platformId,
            'memberId' => $memberId,
            'amount' => 1
        ];
        $this->post(static::API_TRANSFER_WALLET, $data)
            ->assertStatus(200);

        # edit game
        $data = [
            'platformId' => $platformId,
            'memberId' => $memberId,
            'amount' => 1
        ];
        $this->post(static::API_EDIT_GAME, $data)
            ->assertStatus(200);

        # platform options
        $this->call('GET', static::API_PLATFORM_OPTIONS)
            ->assertStatus(200);

        # platform wallet
        $data = [
            'memberId' => $memberId,
            'platformId' => $platformId
        ];
        $this->call('GET', static::API_PLATFORM_WALLET, $data)
            ->assertStatus(200);

        # platform toggle enabled
        /**
         * 測試沒過原因可能是
         * member_platform_active.platform_id 在 game_platform 沒有該 id
         */
        $memberPlatformActive = MemberPlatformActive::where('active_status', 'completed')
            ->get()
            ->random();
        $enabled = ($memberPlatformActive->enabled == 0) ? 1 : 0;
        $data = [
            'memberId' => $memberPlatformActive->member_id,
            'platformId' => $memberPlatformActive->platform_id,
            'enabled' => $enabled
        ];

        $this->post(static::API_PLATFORM_TOGGLE_ENABLED, $data)
            ->assertStatus(200);

        $memberPlatformActive = $memberPlatformActive->fresh();

        $this->assertEquals($memberPlatformActive->enabled, $enabled);

        # related agent
        $data = [];
        $this->call('GET', static::API_RELATED_AGENTS, $data)
            ->assertStatus(200)
            ->assertExactJson([
                'data' => []
            ]);


        $member = Member::all()->random();
        $relatedAgent = Agent::select(['id', 'account', 'name'])
            ->whereIn('id', $member->parentIds())
            ->orderBy('account')
            ->get()
            ->toArray();

        $data = [
            'id' => $member->id
        ];
        $this->call('GET', static::API_RELATED_AGENTS, $data)
            ->assertStatus(200)
            ->assertExactJson([
                'data' => $relatedAgent
            ]);

        # wallet logs
        $logMemberWallet = LogMemberWallet::all()->random();
        $data = [
            'memberId' => $logMemberWallet->member_id,
            'startTime' => $logMemberWallet->created_at->format('Y-m-d H:i:s'),
            'endTime' => $logMemberWallet->created_at->format('Y-m-d H:i:s'),
        ];

        $this->call('GET', static::API_WALLET_LOGS, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'content' => [
                        [
                            'id',
                            'type',
                            'changeBonus',
                            'beforeBonus',
                            'afterBonus',
                            'changeMoney',
                            'beforeMoney',
                            'afterMoney',
                            'editorId',
                            'editorAccount',
                            'remark',
                            'createdAt'
                        ]
                    ],
                    'page',
                    'perPage',
                    'total'
                ]
            ]);

        # transfer logs
        $logTransferMember = LogMemberTransfer::all()->random();
        $data = [
            'memberId' => $logTransferMember->member_id,
            'platformId' => $logTransferMember->platform_id,
            'startTime' => $logTransferMember->created_at->format('Y-m-d H:i:s'),
            'endTime' => $logTransferMember->created_at->format('Y-m-d H:i:s')
        ];
        $this->call('GET', static::API_TRANSFER_LOGS, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'content' => [
                        [
                            'id',
                            'type',
                            'platformId',
                            'platformName',
                            'couponId',
                            'couponName',
                            'couponAmount',
                            'mainAmount',
                            'transferId',
                            'targetAmount',
                            'createdAt'
                        ]
                    ],
                    'page',
                    'perPage',
                    'total'
                ]
            ]);


    }


    /**
     * addMember
     *
     * @return void
     */
    public function testAddMember()
    {
        // ----------- error -----------

        $data = [];
        $this->post(static::API_MEMBER_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'account' => ['The account field is required.'],
                    'agentId' => ['The agent id field is required.'],
                    'clubRankId' => ['The club rank id field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'locked' => ['The locked field is required.'],
                    'gender' => ['The gender field is required.'],
                    'name' => ['The name field is required.'],
                    'nickname' => ['The nickname field is required.'],
                    'password' => ['The password field is required.'],
                    'phone' => ['The phone field is required.'],
                ],
                'message' => 'fail'
            ]);

        $data = [
            'agentId' => 99999,
            'account' => '%^#$&',
            'password' => '$%^%$^%#&',
            'phone' => '^%&^*&^(',
            'clubRankId' => 99999,
            'name' => '$#&%$#&%',
            'nickname' => '^%$^&#',
            'gender' => 'xx',
            'enabled' => '1',
            'locked' => '0'
        ];
        $this->post(static::API_MEMBER_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'agentId' => ['The selected agent id is invalid.'],
                    'clubRankId' => ['The selected club rank id is invalid.'],
                    'gender' => ['The selected gender is invalid.'],
                ],
                'message' => 'fail'
            ]);

        // ----------- success -----------

        $data = [
            'agentId' => Agent::where('level', 5)->get()->random()->id,
            'account' => 'aaaaaaaa99999999',
            'password' => 'aaaaaaaa99999999',
            'phone' => '87878787878787878787',
            'clubRankId' => ClubRank::all()->random()->id,
            'name' => 'nnnnnnn',
            'nickname' => 'mmmmmmm',
            'birth' => '2020-01-01',
            'gender' => 'NA',
            'email' => 'aa455454@',
            'enabled' => 0,
            'locked' => 0
        ];
        $this->post(static::API_MEMBER_ADD, $data)
            ->assertStatus(200);

    }

    /**
     * editMember
     *
     * @return void
     */
    public function testEditMember()
    {

        // ----------- error -----------

        $data = [];
        $this->post(static::API_MEMBER_EDIT, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'id' => ['The id field is required.'],
                    'phone' => ['The phone field is required.'],
                    'clubRankId' => ['The club rank id field is required.'],
                    'name' => ['The name field is required.'],
                    'nickname' => ['The nickname field is required.'],
                    'gender' => ['The gender field is required.'],
                    'enabled' => ['The enabled field is required.'],
                    'locked' => ['The locked field is required.']
                ]
            ]));

        $data = [
            'id' => 999999,
            'phone' => '$%#%#&%^',
            'clubRankId' => 999999,
            'name' => 123,
            'nickname' => 123456,
            'gander' => '',
            'qq' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx11111111111',
            'enabled' => '1',
            'locked' => '0'
        ];
        $this->post(static::API_MEMBER_EDIT, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'clubRankId' => ['The selected club rank id is invalid.'],
                    'name' => ['The name must be a string.'],
                    'nickname' => ['The nickname must be a string.'],
                    'gender' => ['The gender field is required.'],
                    'qq' => ['The qq may not be greater than 30 characters.']
                ]
            ]));

        // ----------- success -----------

        $memberId = Member::all()->random()->id;
        $phone = '123456789';
        $clubRankId = ClubRank::all()->random()->id;
        $name = 'nnnnnnnnn';
        $nickname = 'mmmmmmm';
        $gender = 'NA';
        $locked = 0;
        $enabled = 1;

        $data = [
            'id' => $memberId,
            'phone' => $phone,
            'clubRankId' => $clubRankId,
            'name' => $name,
            'nickname' => $nickname,
            'gender' => $gender,
            'enabled' => $enabled,
            'locked' => $locked
        ];
        $this->post(static::API_MEMBER_EDIT, $data)
            ->assertStatus(200);

        // 驗證
        $member = Member::findOrError($memberId);
        $this->assertEquals($member->phone, $phone);
        $this->assertEquals($member->club_rank_id, $clubRankId);
        $this->assertEquals($member->name, $name);
        $this->assertEquals($member->nickname, $nickname);
        $this->assertEquals($member->gender, $gender);
        $this->assertEquals($member->enabled, $enabled);
        $this->assertEquals($member->locked, $locked);

    }

    /**
     * editPassword
     *
     * @return void
     */
    public function testEditPwd()
    {

        // ----------- error -----------

        $data = [];
        $this->post(static::API_MEMBER_EDIT_PWD, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'id' => ['The id field is required.'],
                    'password' => ['The password field is required.']
                ]
            ]));

        $data = [
            'id' => 99999,
            'password' => 123456789
        ];
        $this->post(static::API_MEMBER_EDIT_PWD, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'id' => ['The selected id is invalid.'],
                    'password' => ['The password must be a string.']
                ]
            ]));

        // ----------- success -----------

        $data = [
            'id' => Member::all()->random()->id,
            'password' => 'xxxxxxxxxxx'
        ];
        $this->post(static::API_MEMBER_EDIT_PWD, $data)
            ->assertStatus(200);

    }

}
