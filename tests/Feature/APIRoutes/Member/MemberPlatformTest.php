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

class MemberPlatformtTest extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    const API_PLATFORM_OPTIONS = '/api/member/platform-options';
    const API_PLATFORM_WALLET = '/api/member/platform-wallet';

    public function testPlatformOptions()
    {
        $this->call('GET', static::API_PLATFORM_OPTIONS)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'key',
                        'name'
                    ]
                ]
            ]);
    }

    public function testPlatformWallet()
    {

        // -------------- error --------------

        $data = [];
        $this->call('GET', static::API_PLATFORM_WALLET, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'memberId' => ['The member id field is required.'],
                    'platformId' => ['The platform id field is required.']
                ]
            ]));

        $data = [
            'memberId' => 999999,
            'platformId' => 999999
        ];
        $this->call('GET', static::API_PLATFORM_WALLET, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'fail',
                'errors' => [
                    'memberId' => ['The selected member id is invalid.'],
                    'platformId' => ['The selected platform id is invalid.']
                ]
            ]));

        // empty member active
        $memberPlatform = MemberPlatformActive::where('active_status', '<>', 'completed')
            ->whereHas('member', function () {
                Member::exists();
            })->whereHas('gamePlatform', function () {
                GamePlatform::exists();
            })->get()
            ->random();
        $data = [
            'memberId' => $memberPlatform->member_id,
            'platformId' => $memberPlatform->platform_id
        ];
        $this->call('GET', static::API_PLATFORM_WALLET, $data)
            ->assertStatus(400)
            ->assertSee(json_encode([
                'message' => 'empty member active'
            ]));

        // -------------- success --------------

        $memberPlatform = MemberPlatformActive::where('active_status', 'completed')
            ->where(function ($query) {
                $query->whereHas('member', function () {
                    Member::exists();
                })->whereHas('gamePlatform', function () {
                    GamePlatform::exists();
                });
            })->get()
            ->random();

        $data = [
            'memberId' => $memberPlatform->member_id,
            'platformId' => $memberPlatform->platform_id
        ];

        $this->call('GET', static::API_PLATFORM_WALLET, $data)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'username',
                    'amount',
                    'enabled',
                    'activeStatus',
                    'configEnabled',
                    'changePasswordFail'
                ]
            ]);

    }
}


