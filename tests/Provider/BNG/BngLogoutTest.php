<?php
use Tests\Provider\BNG\BngBase;
use App\Models\MemberPlatformActive;
use App\Models\Member;
use App\Models\MemberWallet;

class BngLogoutTest extends BngBase
{

    private $testHidden = [
        'testLogoutSuccess' => 0,
    ];

    public function testLogoutSuccess()
    {
        if ($this->testHidden['testLogoutSuccess'] ?? 0) {
            return $this->assertTrue(true);
        }

        $game = $this->getGame();
        $sendAt = new DateTime();
        $now = time();
        $member = $this->getMember();
        $active = $this->getActive($member, $game);

        $data = [
            'name' => "logout",
            'uid' => $now,
            "token" => 'abcdefg',
            "session" => "session-{$now}",
            "game_id" => $game->code,
            "game_name" => $game->name_en,
            "provider_id" => 1,
            "provider_name" => "test",
            'c_at' => $sendAt->format('c'),
            'sent_at' => $sendAt->format('c'),
            'args' => [
                'reason' => "SESSION_CLOSED",
                'player' => [
                    'mode' => "REAL",
                    'currency' => "CNY",
                    'id' => $active->player_id,
                    'is_test' => false
                ],
                'tag' => ""
            ]
        ];

        $this->post('/provider/bng', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'uid' => $now
            ]);
    }
}
