<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Member;
use App\Models\MemberWallet;
use App\Models\Agent;
use App\Models\AgentWallet;
use App\Models\GamePlatform;
use App\Models\MemberPlatformActive;
use Illuminate\Support\Facades\DB;

class MemberWalletTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_LOGIN = '/api/public/login';
    const API_EDIT_MONEY = '/api/member/wallet/edit-money';
    const API_EDIT_BONUS = '/api/member/wallet/edit-bonus';
    const API_GIVE_MONEY = '/api/member/wallet/give-money';
    const API_TAKE_BACK = '/api/member/wallet/take-back';
    const API_TRANSFER_GAME = '/api/member/wallet/transfer-game';
    const API_TRANSFER_WALLET = '/api/member/wallet/transfer-wallet';
    const API_EDIT_GAME = '/api/member/wallet/edit-game';

    /**
     * editMoney
     *
     * @return void
     */
    public function testEditMoney()
    {

        // ----------- error -----------

        $data = [];
        $this->post(static::API_EDIT_MONEY, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'id' => ['The id field is required.'],
                    'amount' => ['The amount field is required.']
                ]
            ]));

        $data = [
            'id' => 99999999,
            'amount' => 10000000000.111,
            'remark' => ''
        ];
        $this->post(static::API_EDIT_MONEY, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'id' => ['The selected id is invalid.']
                ]
            ]));

        // ----------- success -----------

        $memberWallet = MemberWallet::whereHas('member', function () {
            Member::exists();
        })
            ->get()
            ->random();
        $beforeMoney = $memberWallet->money;
        $amount = -10;
        $data = [
            'id' => $memberWallet->id,
            'amount' => $amount
        ];
        $this->post(static::API_EDIT_MONEY, $data)
            ->assertStatus(200);

        $memberWallet = $memberWallet->fresh();

        $this->assertEquals($memberWallet->money, $beforeMoney + $amount);

    }

    /**
     * editBonus
     *
     * @return void
     */
    public function testEditBonus()
    {

        // ----------- error -----------

        $data = [];
        $this->post(static::API_EDIT_BONUS, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'id' => ['The id field is required.'],
                    'amount' => ['The amount field is required.']
                ]
            ]));

        $data = [
            'id' => 99999999,
            'amount' => 10000000000
        ];
        $this->post(static::API_EDIT_BONUS, $data)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'id' => ['The selected id is invalid.']
                ]
            ]));

        // ----------- success -----------

        $memberWallet = MemberWallet::whereHas('member', function () {
            Member::exists();
        })
            ->get()
            ->random();
        $amount = 10;
        $beforeBonus = $memberWallet->bonus;

        $data = [
            'id' => $memberWallet->id,
            'amount' => $amount
        ];
        $this->post(static::API_EDIT_BONUS, $data)
            ->assertStatus(200);

        $memberWallet = $memberWallet->fresh();

        $this->assertEquals($memberWallet->bonus, $beforeBonus + $amount);

    }

    /**
     * giveMoney
     *
     * @return void
     */
    public function testGiveMoney()
    {

        // ----------- error -----------

        $data = [];
        $this->post(static::API_GIVE_MONEY, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'agentId' => ['The agent id field is required.'],
                    'memberId' => ['The member id field is required.'],
                    'amount' => ['The amount field is required.']
                ]
            ]));

        $data = [
            'agentId' => 99999,
            'memberId' => 99999,
            'amount' => -10,
            'remark' => ''
        ];
        $this->post(static::API_GIVE_MONEY, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'agentId' => ['The selected agent id is invalid.'],
                    'memberId' => ['The selected member id is invalid.'],
                    'amount' => ['The amount must be at least 0.']
                ]
            ]));

        // 額度不足
        $amount = 10;
        $agentWallet = AgentWallet::whereHas('agent', function ($query) {
            $query->where('level', '>', 1);
        })
            ->where('money', '<', $amount)
            ->get()
            ->random();
        $memberWallet = MemberWallet::whereHas('member', function ($query) {
            Member::exists();
        })
            ->get()
            ->random();
        $beforeMemberMoney = $memberWallet->money;

        $data = [
            'agentId' => $agentWallet->id,
            'memberId' => $memberWallet->id,
            'amount' => $amount
        ];
        $this->post(static::API_GIVE_MONEY, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => '額度不足'
            ]));

        // ----------- success -----------

        $amount = 10;
        $agentWallet = AgentWallet::whereHas('agent', function ($query) {
            $query->where('level', '>', 1);
        })
            ->where('money', '>=', $amount)
            ->get()
            ->random();
        $beforeAgentMoney = $agentWallet->money;
        $memberWallet = MemberWallet::whereHas('member', function () {
            Member::exists();
        })
            ->get()
            ->random();
        $beforeMemberMoney = $memberWallet->money;

        $data = [
            'agentId' => $agentWallet->id,
            'memberId' => $memberWallet->id,
            'amount' => $amount
        ];
        $this->post(static::API_GIVE_MONEY, $data)
            ->assertStatus(200);

        $agentWallet = $agentWallet->fresh();
        $memberWallet = $memberWallet->fresh();

        $this->assertEquals($agentWallet->money, $beforeAgentMoney - $amount);
        $this->assertEquals($memberWallet->money, $beforeMemberMoney + $amount);

    }

    /**
     * takeBack
     *
     * @return void
     */
    public function testTakeBack()
    {

        // ----------- error -----------

        $data = [];
        $this->post(static::API_TAKE_BACK, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'agentId' => ['The agent id field is required.'],
                    'memberId' => ['The member id field is required.'],
                    'amount' => ['The amount field is required.']
                ]
            ]));

        $data = [
            'agentId' => 999999999,
            'memberId' => 99999999,
            'amount' => 10.123456789
        ];
        $this->post(static::API_TAKE_BACK, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'agentId' => ['The selected agent id is invalid.'],
                    'memberId' => ['The selected member id is invalid.'],
                    'amount' => ['The amount must be an integer.']
                ]
            ]));

        // 額度不足
        $amount = 10;
        $agentWallet = AgentWallet::whereHas('agent', function ($query) {
            $query->where('level', '>', 1);
        })
            ->get()
            ->random();
        $memberWallet = MemberWallet::whereHas('member', function ($query) {
            Member::exists();
        })
            ->where('money', '<', $amount)
            ->get()
            ->random();

        $data = [
            'agentId' => $agentWallet->id,
            'memberId' => $memberWallet->id,
            'amount' => $amount
        ];
        $this->post(static::API_TAKE_BACK, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => '額度不足'
            ]));

        // ----------- success -----------

        $amount = 10;
        $agentWallet = AgentWallet::whereHas('agent', function ($query) {
            $query->where('level', '>', 1);
        })
            ->get()
            ->random();
        $beforeAgentMoney = $agentWallet->money;
        $memberWallet = MemberWallet::whereHas('member', function () {
            Member::exists();
        })
            ->where('money', '>=', $amount)
            ->get()
            ->random();
        $beforeMemberMoney = $memberWallet->money;

        $data = [
            'agentId' => $agentWallet->id,
            'memberId' => $memberWallet->id,
            'amount' => $amount
        ];
        $this->post(static::API_TAKE_BACK, $data)
            ->assertStatus(200);

        $agentWallet = $agentWallet->fresh();
        $memberWallet = $memberWallet->fresh();

        $this->assertEquals($agentWallet->money, $beforeAgentMoney + $amount);
        $this->assertEquals($memberWallet->money, $beforeMemberMoney - $amount);

    }

    /**
     * transferGame
     *
     * @return void
     */
    public function testTransferGame()
    {

        // ----------- error -----------

        $data = [];
        $this->post(static::API_TRANSFER_GAME, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'platformId' => ['The platform id field is required.'],
                    'memberId' => ['The member id field is required.'],
                    'amount' => ['The amount field is required.']
                ]
            ]));

        $data = [
            'platformId' => 9999999,
            'memberId' => 9999999,
            'amount' => -10
        ];
        $this->post(static::API_TRANSFER_GAME, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'platformId' => ['The selected platform id is invalid.'],
                    'memberId' => ['The selected member id is invalid.'],
                    'amount' => ['The amount must be at least 0.']
                ]
            ]));

        // 額度不足
        $amount = 100;
        $memberWallet = MemberWallet::whereHas('member', function () {
            Member::exists();
        })
            ->where('money', '<', $amount)
            ->get()
            ->random();

        $data = [
            'platformId' => GamePlatform::all()->random()->id,
            'memberId' => $memberWallet->id,
            'amount' => $amount,
            'remark' => ''
        ];
        $this->post(static::API_TRANSFER_GAME, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => '額度不足'
            ]));

        // empty member active
        $amount = 100;
        $inactiveMember = MemberPlatformActive::where('active_status', '<>', 'completed')
            ->whereHas('gamePlatform', function ($query) {
                GamePlatform::exists();
            })->whereHas('member', function ($query) use ($amount) {
                $query->whereHas('wallet', function ($query) use ($amount) {
                    $query->where('money', '>=', $amount);
                });
            })
            ->get()
            ->random();
        $data = [
            'platformId' => $inactiveMember->platform_id,
            'memberId' => $inactiveMember->member_id,
            'amount' => $amount
        ];
        $this->post(static::API_TRANSFER_GAME, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'empty member active'
            ]));

        // ----------- success -----------

        /**
         * 條件:
         * MemberPlatformActive.active_status=='complete'
         * MemebrWallet.money >= $amount
         * AgentPlatformConfig.agent_id = member.lv5
         */
        $amount = 100;
        $memberPlatform = MemberPlatformActive::where('active_status', 'completed')
            ->whereHas('gamePlatform', function ($query) {
                $query->where('maintain', 0);
            })
            ->whereHas('member', function ($query) use ($amount) {
                $query->whereHas('wallet', function ($query) use ($amount) {
                    $query->where('money', '>', $amount);
                })->whereExists(function ($query) {
                    $query->select('*')
                        ->from('agent')
                        ->whereRaw('agent.id', 'member.alv5');
                });
            })
            ->get()
            ->random();

        $data = [
            'platformId' => $memberPlatform->platform_id,
            'memberId' => $memberPlatform->member_id,
            'amount' => $amount,
            'remark' => 'test'
        ];
        $this->post(static::API_TRANSFER_GAME, $data)
            ->assertStatus(200);

    }

    /**
     * transferWallet
     *
     * @return void
     */
    public function testTransferWallet()
    {

        // ----------- error -----------

        $data = [];
        $this->post(static::API_TRANSFER_WALLET, $data)
            ->assertStatus(400)
            ->assertJson([
                'message' => 'fail',
                'errors' => [
                    'platformId' => ['The platform id field is required.'],
                    'memberId' => ['The member id field is required.'],
                    'amount' => ['The amount field is required.']
                ]
            ]);

        $data = [
            'platformId' => 9999999,
            'memberId' => 9999999,
            'amount' => -10
        ];
        $this->post(static::API_TRANSFER_WALLET, $data)
            ->assertStatus(400)
            ->assertJson([
                'message' => 'fail',
                'errors' => [
                    'platformId' => ['The selected platform id is invalid.'],
                    'memberId' => ['The selected member id is invalid.'],
                    'amount' => ['The amount must be at least 0.']
                ]
            ]);

        // 額度不足
        $amount = 100;
        $memberWallet = MemberWallet::whereHas('member', function () {
            Member::exists();
        })
            ->where('money', '<', $amount)
            ->get()
            ->random();

        $data = [
            'platformId' => GamePlatform::all()->random()->id,
            'memberId' => $memberWallet->id,
            'amount' => $amount,
            'remark' => ''
        ];
        $this->post(static::API_TRANSFER_WALLET, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => '額度不足'
            ]));

        // ----------- success -----------

        /**
         * 條件:
         * MemberPlatformActive.active_status=='complete'
         * MemebrWallet.money >= $amount
         * AgentPlatformConfig.agent_id = member.lv5
         */
        $amount = 100;
        $memberPlatform = MemberPlatformActive::where('active_status', 'completed')
            ->whereHas('gamePlatform', function ($query) {
                $query->where('maintain', 0);
            })
            ->whereHas('member', function ($query) use ($amount) {
                $query->whereHas('wallet', function ($query) use ($amount) {
                    $query->where('money', '>', $amount);
                })->whereExists(function ($query) {
                    $query->select('*')
                        ->from('agent')
                        ->whereRaw('agent.id', 'member.alv5');
                });
            })
            ->get()
            ->random();

        $data = [
            'platformId' => $memberPlatform->platform_id,
            'memberId' => $memberPlatform->member_id,
            'amount' => $amount,
            'remark' => 'test'
        ];
        $this->post(static::API_TRANSFER_WALLET, $data)
            ->assertStatus(200);

    }

    /**
     * editGame
     *
     * @return void
     */
    public function testEditGame()
    {

        // ----------- error -----------

        $data = [];
        $this->post(static::API_EDIT_GAME, $data)
            ->assertStatus(400)
            ->assertJson([
                'message' => 'fail',
                'errors' => [
                    'platformId' => ['The platform id field is required.'],
                    'memberId' => ['The member id field is required.'],
                    'amount' => ['The amount field is required.']
                ]
            ]);

        $data = [
            'platformId' => 99999,
            'memberId' => 99999,
            'amount' => -10.111
        ];
        $this->post(static::API_EDIT_GAME, $data)
            ->assertStatus(400)
            ->assertJson([
                'message' => 'fail',
                'errors' => [
                    'platformId' => ['The selected platform id is invalid.'],
                    'memberId' => ['The selected member id is invalid.'],
                ]
            ]);

        // empty member active
        $amount = 100;
        $inactiveMember = MemberPlatformActive::where('active_status', '<>', 'completed')
            ->whereHas('gamePlatform', function ($query) {
                GamePlatform::exists();
            })->whereHas('member', function ($query) use ($amount) {
                $query->whereHas('wallet', function ($query) use ($amount) {
                    $query->where('money', '>=', $amount);
                });
            })
            ->get()
            ->random();
        $data = [
            'platformId' => $inactiveMember->platform_id,
            'memberId' => $inactiveMember->member_id,
            'amount' => $amount
        ];
        $this->post(static::API_EDIT_GAME, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'empty member active'
            ]));

        // ----------- success -----------

        $amount = 100;
        $memberPlatform = MemberPlatformActive::where('active_status', 'completed')
            ->whereHas('gamePlatform', function ($query) {
                $query->where('maintain', 0);
            })
            ->whereHas('member', function ($query) use ($amount) {
                $query->whereHas('wallet', function ($query) use ($amount) {
                    $query->where('money', '>', $amount);
                })->whereExists(function ($query) {
                    $query->select('*')
                        ->from('agent')
                        ->whereRaw('agent.id', 'member.alv5');
                });
            })
            ->get()
            ->random();

        $data = [
            'platformId' => $memberPlatform->platform_id,
            'memberId' => $memberPlatform->member_id,
            'amount' => $amount,
            'remark' => 'test'
        ];
        $this->post(static::API_EDIT_GAME, $data)
            ->assertStatus(200);



    }

}


