<?php
use Tests\Provider\BNG\BngBase;
use App\Models\MemberPlatformActive;
use App\Models\Member;
use App\Models\MemberWallet;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Provider\AccessService;

class BngLoginTest extends BngBase
{
    use WithoutMiddleware;


    public function testInvalidToken()
    {

        $game = $this->getGame();
        $sendAt = new DateTime();
        $uid = time();
        $data = [
            "name" => "login",
            "uid" => $uid,
            "token" => 'abcdefg',
            "session" => "session-{$uid}",
            "game_id" => $game->code,
            "game_name" => $game->name_en,
            "provider_id" => 1,
            "provider_name" => "test",
            "c_at" => $sendAt->format('c'),
            "sent_at" => $sendAt->format('c'),
            "args" => [
                "platform" => "DESKTOP"
            ]
        ];

        $this->post('/provider/bng', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'uid' => $uid,
                'error' => [
                    'code' => 'INVALID_TOKEN',
                    'message' => 'invalid-token',
                ]
            ]);
    }

    public function testExpiredToken()
    {

        $game = $this->getGame();
        $sendAt = new DateTime();
        $now = time();
        $token = md5("access-token-{$now}") . '-' . $now;
        $data = [
            "name" => "login",
            "uid" => $now,
            "token" => $token,
            "session" => "session-{$now}",
            "game_id" => $game->code,
            "game_name" => $game->name_en,
            "provider_id" => 1,
            "provider_name" => "test",
            "c_at" => $sendAt->format('c'),
            "sent_at" => $sendAt->format('c'),
            "args" => [
                "platform" => "DESKTOP"
            ]
        ];

        $this->post('/provider/bng', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'uid' => $now,
                'error' => [
                    'code' => 'EXPIRED_TOKEN',
                    'message' => 'expired-token',
                ]
            ]);
    }

    public function testGameNotAllowed()
    {

        $game = $this->getGame();
        $sendAt = new DateTime();
        $now = time();
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $access = new AccessService($active);
        $token = $access->generateAccessToken();

        $data = [
            "name" => "login",
            "uid" => $now,
            "token" => $token,
            "session" => "session-{$now}",
            "game_id" => '-1',
            "game_name" => $game->name_en,
            "provider_id" => 1,
            "provider_name" => "test",
            "c_at" => $sendAt->format('c'),
            "sent_at" => $sendAt->format('c'),
            "args" => [
                "platform" => "DESKTOP"
            ]
        ];

        $this->post('/provider/bng', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'uid' => $now,
                'error' => [
                    'code' => 'GAME_NOT_ALLOWED',
                    'message' => 'game not found',
                ]
            ]);
    }
    public function testLoginSuccess()
    {

        $game = $this->getGame();
        $sendAt = new DateTime();
        $now = time();
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $access = new AccessService($active);
        $token = $access->generateAccessToken();

        $wallet = MemberWallet::findOrError($member->id);

        $data = [
            "name" => "login",
            "uid" => $now,
            "token" => $token,
            "session" => "session-{$now}",
            "game_id" => $game->code,
            "game_name" => $game->name_en,
            "provider_id" => 1,
            "provider_name" => "test",
            "c_at" => $sendAt->format('c'),
            "sent_at" => $sendAt->format('c'),
            "args" => [
                "platform" => "DESKTOP"
            ]
        ];

        $this->post('/provider/bng', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'uid' => $now,
                'tag' => '',
                'balance' => [
                    'value' => strval($wallet->money),
                    'version' => intval($wallet->version),
                ],
                'player' => [
                    'id' => $active->player_id,
                    'currency' => $this->platform->currency,
                    'is_test' => false,
                    'mode' => 'REAL',
                ]
            ]);
    }
}
