<?php

namespace Tests\Feature\APIRoutes\Report;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\Services\Report\ResultReportService;
use App\Models\Report\PlatformReportAg;
use App\Models\Agent;
use App\Models\GameType;
use App\Models\GamePlatform;
use App\Models\Report\PlatformReportAgDetail;
use Illuminate\Support\Carbon;
use App\Models\Game;

class AgentTest extends TestCase
{
    use DatabaseTransactions;
    use WithoutMiddleware;
    // use RefreshDatabase;

    const API_RESULT_AGENT = '/api/report/result-agent';
    const API_RESULT_TYPE = '/api/report/result-type';
    const API_RESULT_MEMBER = '/api/report/result-member';
    const API_RESULT_MEMBER_DETAIL = '/api/report/result-member/detail';
    const API_AGENT_OPTIONS = '/api/report/agent-options';
    const API_MEMBER_OPTIONS = '/api/report/member-options';

    /**
     * resultAgent
     *
     * @return void
     */
    public function testResultAgent()
    {
        // --------------- error ---------------

        $data = [];
        $this->call('get', static::API_RESULT_AGENT, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'errors' => [
                    'startTime' => ['The start time field is required.'],
                    'endTime' => ['The end time field is required.'],
                    'platformId' => ['The platform id field is required.']
                ]
            ]));

        /**
         * 新增測試資料
         * create() 會寫入資料庫
         * make() 不會
         */
        $platformReportAg = factory(PlatformReportAg::class)->make();

        // test
        $data = [
            'startTime' => '$%#^%',
            'endTime' => '$&^%#',
            'platformId' => 999999,
            'parentId' => 999999,
            'agentId' => 999999,
            'gameType' => '#$&',
            'gameId' => 99999
        ];
        $this->call('get', static::API_RESULT_AGENT, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'errors' => [
                    'startTime' => ['The start time is not a valid date.'],
                    'endTime' => ['The end time is not a valid date.'],
                    'platformId' => ['The selected platform id is invalid.'],
                    'parentId' => ['The selected parent id is invalid.'],
                    'agentId' => ['The selected agent id is invalid.'],
                    'gameType' => ['The selected game type is invalid.'],
                    'gameId' => ['The selected game id is invalid.']
                ]
            ]));

        // 查詢公司層級
        $company = Agent::where('level', 0)->first();
        $parentId = $company->id;
        $data = [
            'startTime' => '2017-01-01 00:00:00',
            'endTime' => '2017-12-31 23:59:59',
            'platformId' => 24,
            'parentId' => $parentId
        ];
        $ag = Agent::findOrError($parentId);

        $this->call('GET', static::API_RESULT_AGENT, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => "error agent level > {$ag->account}"
            ]));


        // parentId 帶最低層級
        $lowestAg = Agent::where('level', 5)->first();
        $parentId = $lowestAg->id;

        $data = [
            'startTime' => '2017-01-01 00:00:00',
            'endTime' => '2017-12-31 23:59:59',
            'platformId' => 24,
            'parentId' => $parentId
        ];
        $ag = Agent::findOrError($parentId);

        $this->call('GET', static::API_RESULT_AGENT, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => "error agent level = {$ag->level}"
            ]));

        // --------------- success ---------------

        /**
         * 新增測試資料
         */
        $platformReportAg = factory(PlatformReportAg::class)->create();

        $data = [
            'startTime' => $platformReportAg->bet_at,
            'endTime' => '2019-12-31 23:59:59',
            'platformId' => 24,
            'type' => $platformReportAg->type,
            'gameId' => $platformReportAg->game_id
        ];

        $this->call('GET', static::API_RESULT_AGENT, $data)
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    [
                        'agentId' => $platformReportAg->alv1,  // 參數parentId沒指定，agentId就是拿第一層的
                        'agentAccount' => Agent::findOrError($platformReportAg->alv1)->account,
                        'nums' => 1,
                        'validAmount' => $platformReportAg->valid_amount,
                        'betAmount' => $platformReportAg->bet_amount,
                        'waterAmount' => $platformReportAg->water_amount,
                        'resultAmount' => $platformReportAg->result_amount,
                        'prize' => $platformReportAg->prize,
                        'tip' => $platformReportAg->tip,
                        'subtotal' => $platformReportAg->subtotal,
                        'agentAmount' => $platformReportAg->alv1_amount,
                        'agentWaterAmount' => $platformReportAg->alv1_water_amount,
                        'costAgentWaterAmount' => $platformReportAg->alv1_cost_agent_water_amount,
                        'costMemberWaterAmount' => $platformReportAg->alv1_cost_member_water_amount,
                        'costMemberBonusAmount' => $platformReportAg->alv1_cost_member_bonus_amount
                    ]
                ]
            ]);

    }

    /**
     * resultType
     *
     * @return void
     */
    public function testResultType()
    {

        // --------------- error ---------------

        $data = [];
        $this->call('GET', static::API_RESULT_TYPE, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'errors' => [
                    'startTime' => ['The start time field is required.'],
                    'endTime' => ['The end time field is required.'],
                    'platformId' => ['The platform id field is required.']
                ]
            ]));


        /**
         * 新增測試資料
         */
        $platformReportAg = factory(PlatformReportAg::class)->make();

        // test
        $data = [
            'startTime' => '$%#^%',
            'endTime' => '$&^%#',
            'platformId' => 999999,
            'agentId' => 999999,
            'gameType' => '#$&',
        ];
        $this->call('get', static::API_RESULT_TYPE, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'errors' => [
                    'startTime' => ['The start time is not a valid date.'],
                    'endTime' => ['The end time is not a valid date.'],
                    'platformId' => ['The selected platform id is invalid.'],
                    'agentId' => ['The selected agent id is invalid.'],
                    'gameType' => ['The selected game type is invalid.'],
                ]
            ]));

        // 查詢公司層級
        $company = Agent::where('level', 0)->first();
        $agentId = $company->id;
        $data = [
            'startTime' => '2017-01-01 00:00:00',
            'endTime' => '2017-12-31 23:59:59',
            'platformId' => 24,
            'agentId' => $agentId
        ];

        $this->call('GET', static::API_RESULT_TYPE, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => "error agent level"
            ]));

        // --------------- success ---------------

        /**
         * 新增測試資料
         */
        $platformReportAg = factory(PlatformReportAg::class)->create();
        $data = [
            'startTime' => $platformReportAg->bet_at,
            'endTime' => '2019-12-31 23:59:59',
            'platformId' => 24,
            'agentId' => '',
            'gameType' => $platformReportAg->type,
        ];

        // test
        $this->call('GET', static::API_RESULT_TYPE, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'type',
                        'typeName',
                        'platformId',
                        'platformName',
                        'nums',
                        'validAmount',
                        'betAmount',
                        'waterAmount',
                        'resultAmount',
                        'prize',
                        'tip',
                        'subtotal'
                    ]
                ]
            ]);

    }

    /**
     * resultMember
     *
     * @return void
     */
    public function testResultMember()
    {
        // --------------- error ---------------

        $data = [];
        $this->call('GET', static::API_RESULT_MEMBER, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'errors' => [
                    'startTime' => ['The start time field is required.'],
                    'endTime' => ['The end time field is required.'],
                    'platformId' => ['The platform id field is required.']
                ]
            ]));

        $data = [
            'startTime' => '$%#^%',
            'endTime' => '$&^%#',
            'platformId' => 999999,
            'agentId' => 999999,
            'memberId' => 999999,
            'gameType' => '#$&',
            'gameId' => 99999,
            'page' => 'xxx',
            'perPage' => 'zzz',
            'sorts' => '*&^&%#'
        ];
        $this->call('get', static::API_RESULT_MEMBER, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'errors' => [
                    'startTime' => ['The start time is not a valid date.'],
                    'endTime' => ['The end time is not a valid date.'],
                    'platformId' => ['The selected platform id is invalid.'],
                    'agentId' => ['The selected agent id is invalid.'],
                    'memberId' => ['The selected member id is invalid.'],
                    'gameType' => ['The selected game type is invalid.'],
                    'gameId' => ['The selected game id is invalid.']
                ]
            ]));

        // 查詢公司層級
        $company = Agent::where('level', 0)->first();
        $agentId = $company->id;
        $data = [
            'startTime' => '2017-01-01 00:00:00',
            'endTime' => '2017-12-31 23:59:59',
            'platformId' => 24,
            'agentId' => $agentId
        ];

        $this->call('GET', static::API_RESULT_MEMBER, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => "error agent level"
            ]));

        // --------------- success ---------------

        /**
         * 新增測試資料
         */
        $platformReportAg = factory(PlatformReportAg::class)->create();
        $data = [
            'startTime' => $platformReportAg->bet_at,
            'endTime' => '2019-12-31 23:59:59',
            'platformId' => 24,
            'agentId' => '',
            'memberId' => $platformReportAg->member_id,
            'gameType' => $platformReportAg->type,
            'gameId' => $platformReportAg->game_id
        ];
        $this->call('GET', static::API_RESULT_MEMBER, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'content' => [
                        [
                            'memberId',
                            'memberAccount',
                            'nums',
                            'validAmount',
                            'betAmount',
                            'waterAmount',
                            'resultAmount',
                            'prize',
                            'tip',
                            'subtotal'
                        ]
                    ]
                ]
            ]);
    }

    /**
     * resultMemberDetail
     *
     * @return void
     */
    public function testResultMemberDetail()
    {

        // --------------- error ---------------

        $data = [];
        $this->call('GET', static::API_RESULT_MEMBER_DETAIL, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'errors' => [
                    'memberId' => ['The member id field is required.'],
                    'startTime' => ['The start time field is required.'],
                    'endTime' => ['The end time field is required.'],
                    'platformId' => ['The platform id field is required.']
                ]
            ]));

        $data = [
            'memberId' => 99999,
            'startTime' => 'adssafsdfsf',
            'endTime' => '2020-01-33',
            'platformId' => 99999,
            'gameType' => '%^#$%$#@',
            'gameId' => 9999,
            'page' => 'xxx',
            'perPage' => 'zzz',
            'sorts' => 123
        ];
        $this->call('GET', static::API_RESULT_MEMBER_DETAIL, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'errors' => [
                    'memberId' => ['The selected member id is invalid.'],
                    'startTime' => ['The start time is not a valid date.'],
                    'endTime' => ['The end time is not a valid date.'],
                    'platformId' => ['The selected platform id is invalid.'],
                    'gameType' => ['The selected game type is invalid.'],
                    'gameId' => ['The selected game id is invalid.']
                ]
            ]));


        // --------------- success ---------------

        /*  關聯 platform_report_ag, platform_report_ag_detail 的所有資料
        $platformReportAg = factory(PlatformReportAg::class)
            ->create()
            ->each(function ($detail) {
                $detail->detail()
                    ->save(factory(PlatformReportAgDetail::class)->create([
                        'bet_at' => $detail->bet_at,
                        'lottery_at' => $detail->lottery_at
                    ]));
            });
         */


        /**
         * 新建測試資料
         * 關聯 PlatformReportAg, PlatformReportAgDetail 兩張表
         */
        $platformReportAg = factory(PlatformReportAg::class)
            ->create();
        $platformReportAgDetail = $platformReportAg
            ->detail()
            ->save(factory(PlatformReportAgDetail::class)->create([
                'bet_at' => $platformReportAg->bet_at,
                'lottery_at' => $platformReportAg->lottery_at
            ]));

        $data = [
            'memberId' => $platformReportAg->member_id,
            'startTime' => $platformReportAg->bet_at,
            'endTime' => Carbon::now()->toDateTimeString(),
            'platformId' => $platformReportAg->platform_id,
            'gameType' => $platformReportAg->type,
            'gameId' => $platformReportAg->game_id
        ];

        $this->call('GET', static::API_RESULT_MEMBER_DETAIL, $data)
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    'content' => [
                        [
                            'betId' => "$platformReportAg->id",
                            'platformId' => $platformReportAg->platform_id,
                            'platformName' => GamePlatform::findOrError($platformReportAg->platform_id)->name,
                            'gameId' => $platformReportAg->game_id,
                            'gameName' => Game::findOrError($platformReportAg->game_id)->name,
                            'gameType' => $platformReportAg->type,
                            'validAmount' => $platformReportAg->valid_amount,
                            'betAmount' => $platformReportAg->bet_amount,
                            'waterAmount' => $platformReportAg->water_amount,
                            'resultAmount' => $platformReportAg->result_amount,
                            'prize' => $platformReportAg->prize,
                            'tip' => $platformReportAg->tip,
                            'subtotal' => $platformReportAg->subtotal,
                            'round' => $platformReportAgDetail->round,
                            'content' => $platformReportAg->content,
                            'status' => $platformReportAg->status,
                            'ip' => $platformReportAgDetail->ip,
                            'lotteryResult' => $platformReportAgDetail->lottery_result,
                            'reportId' => $platformReportAgDetail->report_id,
                            'betAt' => $platformReportAg->bet_at,
                            'lotteryAt' => $platformReportAg->lottery_at
                        ]
                    ],
                    'page' => 1,
                    'perPage' => 15,
                    'total' => 1,
                ]
            ]);
    }

    /**
     * agentOptions
     *
     * @return void
     */
    public function testAgentOptions()
    {
        // --------------- error ---------------

        $data = [];
        $this->call('GET', static::API_AGENT_OPTIONS, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'errors' => [
                    'account' => ['The account field is required.']
                ]
            ]));

        $data = [
            'account' => 9999
        ];
        $this->call('GET', static::API_AGENT_OPTIONS, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'errors' => [
                    'account' => ['The account must be a string.']
                ]
            ]));

        // --------------- success ---------------

        $data = [
            'account' => 'c'
        ];
        $this->call('GET', static::API_AGENT_OPTIONS, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'account'
                    ]
                ]
            ]);

    }

    /**
     * getMemberOptions
     *
     * @return void
     */
    public function testMemberOptions()
    {
        // --------------- error ---------------

        $data = [];
        $this->call('GET', static::API_MEMBER_OPTIONS, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'errors' => [
                    'account' => ['The account field is required.']
                ]
            ]));

        // --------------- success ---------------

        $data = [
            'account' => '$%^&%#%^'
        ];
        $this->call('GET', static::API_MEMBER_OPTIONS, $data)
            ->assertStatus(200);

        $data = [
            'account' => 'c'
        ];
        $this->call('GET', static::API_MEMBER_OPTIONS, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'account'
                    ]
                ]
            ]);

    }

}
