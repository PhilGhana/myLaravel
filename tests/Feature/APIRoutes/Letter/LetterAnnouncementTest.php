<?php

namespace Tests\Feature\APIRoutes\Letter;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\Models\LetterAnnouncement;
use Tests\TestCase;
use App\Models\LetterTag;
use App\Models\Agent;
use App\Models\Member;
use App\Models\MemberTag;
use App\Models\Club;
use App\Models\ClubRank;

/**
 * 站內信 - 公告 (letter-announcement)
 */
class LetterAnnouncementTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_LOGIN = '/api/public/login';
    const API_ADD = '/api/letter-announcement/add';
    const API_LIST = '/api/letter-announcement/list';
    const API_TAGS = '/api/letter-announcement/announcement-tags';
    const API_QUERY_AGENT = '/api/letter-announcement/query-agent';
    const API_QUERY_MEMBER = '/api/letter-announcement/query-member';
    const API_MEMBER_TAG_OPTIONS = '/api/letter-announcement/member-tag-options';
    const API_CLUB_OPTIONS = '/api/letter-announcement/club-options';
    const API_CLUB_RANK_OPTIONS = '/api/letter-announcement/club-rank-options';
    const APi_SEND_MEMBERS = '/api/letter-announcement/send-members';

    /**
     * addAnnouncement
     *
     * @return void
     */
    public function testAdd()
    {
        // -------------- error --------------

        $data = [];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'tagId' => ['The tag id field is required.'],
                    'title' => ['The title field is required.'],
                    'content' => ['The content field is required.']
                ],
                'message' => 'fail'
            ]);

        $data = [
            'tagId' => 999,
            'title' => 'xxxxxxxxx',
            'content' => 'xxxxxxxxxxxxxxxxxxxx',
            'all' => 'xxxxx',
            'members' => ['a', 'b', 'c'],
            'clubRanks' => [1, 2, 3, 4, 999],
            'memberTags' => ['aaa'],
            'agents' => [4, 5, 999999],
            'registerStart' => '2018/01/01 00:00:00',
            'registerEnd' => '2017-01-01 00:00:00'
        ];
        $this->post(static::API_ADD, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'tagId' => ['The selected tag id is invalid.'],
                    'members' => ['members not array or type error'],
                    'clubRanks' => ['The selected club ranks is invalid.'],
                    'memberTags' => ['member tags not array or type error'],
                    'agents' => ['The selected agents is invalid.'],
                    'registerStart' => ['The register start does not match the format Y-m-d H:i:s.']
                ],
                'message' => 'fail'
            ]);

        // -------------- success --------------


        // login
        $loginData = [
            'account' => 'chloe',
            'password' => 'chloe'
        ];
        $this->post(static::API_LOGIN, $loginData)
            ->assertOk();

        // all = 1
        $data = [
            'tagId' => 1,
            'title' => 'xxxxxxxxx',
            'content' => 'xxxxxxxxxxxxxxxxxxxx',
            'all' => 1,
        ];

        $this->post(static::API_ADD, $data)
            ->assertOk();

        // all != 1
        $data = [
            'tagId' => 1,
            'title' => 'xxxxxxxxx',
            'content' => 'xxxxxxxxxxxxxxxxxxxx',
            'members' => [1, 2, 3, 4],
            'clubRanks' => [1, 2, 3, 60, 61, 62],
            'memberTags' => [1, 3, 6, 7],
            'agents' => [4, 5],
            'registerStart' => '2018-01-01 00:00:00',
            'registerEnd' => '2017-01-01 00:00:00'
        ];

        $this->post(static::API_ADD, $data)
            ->assertStatus(200);
    }

    /**
     * getAnnouncementList
     *
     * @return void
     */
    public function testList()
    {
        // -------------- error --------------

        $data = [
            'page' => 'aaa',
            'perPage' => 'zzzz'
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertStatus(400)
            ->assertExactJson([
                'errors' => [
                    'page' => ['The page must be a number.'],
                    'perPage' => ['The per page must be a number.']
                ],
                'message' => 'fail'
            ]);

        // -------------- success --------------

        $data = [];
        $this->call('GET', static::API_LIST, $data)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'content' => [
                        [
                            'id',
                            'tagId',
                            'tagName',
                            'title',
                            'content',
                            'numReads',
                            'numSends',
                            'updatedAt',
                            'createdAt'
                        ]
                    ],
                    'page',
                    'perPage',
                    'total'
                ]
            ]);

        $data = [
            'page' => 2,
            'perPage' => 50
        ];
        $this->call('GET', static::API_LIST, $data)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'content' => [],
                    'page',
                    'perPage',
                    'total'
                ]
            ]);
    }

    /**
     * getAnnouncementTags
     *
     * @return void
     */
    public function testGetTags()
    {
        // -------------- success --------------

        $data = [];
        $this->call('GET', static::API_TAGS, $data)
            ->assertOk()
            ->assertJsonStructure([
                'data' => []
            ]);


        // login
        $loginData = [
            'account' => 'chloe',
            'password' => 'chloe'
        ];
        $this->post(static::API_LOGIN, $loginData)
            ->assertOk();

        // 新增測試資料
        $type = 'announcement';
        $name = 'tagName';
        $enabled = 1;
        $letterTag = new LetterTag();
        $letterTag->type = $type;
        $letterTag->name = $name;
        $letterTag->enabled = $enabled;
        $letterTag->saveOrError();

        $letterTag = $letterTag->fresh();

        // test
        $this->call('GET', static::API_TAGS, [])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name'
                    ]
                ]
            ]);
    }

    /**
     * queryAgent
     *
     * @return void
     */
    public function testQueryAgent()
    {

        // -------------- success --------------

        $data = [];
        $this->call('GET', static::API_QUERY_AGENT, $data)
            ->assertStatus(200);

        $data = [
            'account' => 'xxxxxx'
        ];
        $this->call('GET', static::API_QUERY_AGENT, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => []
            ]);

        //新增測試資料
        $agent = new Agent();
        $agent->extend_id = 0;
        $agent->role_id = 0;
        $agent->account = 'chloe888';
        $agent->password = 'chloe';
        $agent->name = 'chloe';
        $agent->enabled = 1;
        $agent->locked = 1;
        $agent->level = 1;
        $agent->lv1 = 0;
        $agent->lv2 = 0;
        $agent->lv3 = 0;
        $agent->lv4 = 0;
        $agent->error_count = 0;
        $agent->saveOrError();

        $agent = $agent->fresh();

        // test
        $data = [
            'account' => 'chloe'
        ];
        $this->call('GET', static::API_QUERY_AGENT, $data)
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
     * queryMember
     *
     * @return void
     */
    public function testQueryMember()
    {

        // -------------- success --------------

        $data = [];
        $this->call('GET', static::API_QUERY_MEMBER, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => []
            ]);

        $data = [
            'account' => 'qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq'
        ];
        $this->call('GET', static::API_QUERY_MEMBER, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => []
            ]);

        // 新增測試資料
        $member = new Member();
        $member->account = 'chloe';
        $member->password = 'chloe';
        $member->name = 'chloe';
        $member->nickname = 'chloe';
        $member->club_id = '123';
        $member->club_rank_id = '123';
        $member->alv1 = 0;
        $member->alv2 = 0;
        $member->alv3 = 0;
        $member->alv4 = 0;
        $member->alv5 = 0;
        $member->mlv3 = 0;
        $member->mlv2 = 0;
        $member->mlv1 = 0;
        $member->gender = 'NA';
        $member->invitation_code = '123456789';
        $member->enabled = 1;
        $member->locked = 1;
        $member->error_count = 0;
        $member->saveOrError();

        $member = $member->fresh();

        $data = [
            'account' => $member->account
        ];
        $this->call('GET', static::API_QUERY_MEMBER, $data)
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
     * memberTagOptions
     *
     * @return void
     */
    public function testMemTagOptions()
    {

        // -------------- success --------------

        // 新增測試資料
        $memeberTag = new MemberTag();
        $memeberTag->name = 'chloe';
        $memeberTag->color = '#ffffff';
        $memeberTag->remark = 'xxxxxxx';
        $memeberTag->saveOrError();

        // test
        $this->call('GET', static::API_MEMBER_TAG_OPTIONS)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'color'
                    ]
                ]
            ]);
    }

    /**
     * clubOptions
     *
     * @return void
     */
    public function testClubOptions()
    {

        // 新增測試資料
        $club = new Club();
        $club->name = 'chloe';
        $club->describe = 'xxxxxxxxxxx';
        $club->remark = 'rrrrrrrrrrrr';
        $club->enabled = 1;
        $club->saveOrError();

        $club = $club->fresh();

        // test
        $this->call('GET', static::API_CLUB_OPTIONS)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name'
                    ]
                ]
            ]);
    }

    /**
     * clubRankOptions
     *
     * @return void
     */
    public function testClubRankOptions()
    {
        $data = [];
        $this->call('GET', static::API_CLUB_RANK_OPTIONS, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => []
            ]);

        // 新增測試資料
        $clubId = 1;
        $clubRank = new ClubRank();
        $clubRank->club_id = $clubId;
        $clubRank->name = 'xxxxx';
        $clubRank->enabled = 1;
        $clubRank->default = 0;
        $clubRank->order = '100';
        $clubRank->deposit_per_max = 0;
        $clubRank->deposit_per_min = 0;
        $clubRank->deposit_day_times = 0;
        $clubRank->withdraw_per_max = 0;
        $clubRank->withdraw_per_min = 0;
        $clubRank->withdraw_day_times = 0;
        $clubRank->saveOrError();

        $clubRank = $clubRank->fresh();

        // test
        $data = [
            'id' => $clubId
        ];

        $this->call('GET', static::API_CLUB_RANK_OPTIONS, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name'
                    ]
                ]
            ]);
    }

    public function testSendMembers ()
    {
        $data = [];
        $this->call('GET', static::APi_SEND_MEMBERS, $data)
            ->assertOk()
            // ->assertSee(json_encode(['654']));
            ->assertJsonStructure([
                'data' => [
                    'content' => [
                        [
                            'id',
                            'account',
                            'name',
                            'readedAt',
                            'removedAt'
                        ]
                    ],
                    'page',
                    'perPage',
                    'total'
                ]
            ]);
            $data = [
                'id' => 1,
                'sorts' => ['id,desc'],
                'page' => 1,
                'perPage' => 1
            ];
            $this->call('GET', static::APi_SEND_MEMBERS, $data)
                ->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'content' => [
                            [
                                'id',
                                'account',
                                'name',
                                'readedAt',
                                'removedAt'
                            ]
                        ],
                        'page',
                        'perPage',
                        'total'
                    ]
                ]);
    }
}
