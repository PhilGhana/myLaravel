<?php
use App\Models\MemberWallet;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Provider\Ka\KaBase;
use ApiGameProvider\Base\Exceptions\InvalidTokenException;
use ApiGameProvider\Base\Exceptions\GameNotAllowedException;
use ApiGameProvider\Base\Exceptions\ExpiredTokenException;
use App\Services\Provider\AccessService;

class KaLoginTest extends KaBase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    public function testInvalidToken()
    {
        $game = $this->getGame();
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $data = $this->requestData($active, $game);

        $data['token'] = 'abcdefg';

        $this->post('/provider/ka', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'status' => InvalidTokenException::class . " => invalid-token",
                'statusCode' => 100,
                'userMessage' => 'invalid-token',
            ]);
    }

    public function testExpiredToken()
    {
        $game = $this->getGame();
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $sendAt = new DateTime();
        $now = time();
        $token = md5("access-token-{$now}") . '-' . $now;
        $data = $this->requestData($active, $game);
        $data['token'] = $token;

        $this->post('/provider/ka', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'status' => ExpiredTokenException::class . " => expired-token",
                'statusCode' => 100,
                'userMessage' => 'expired-token',
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
        $data = $this->requestData($active, $game);

        $data['gameId'] = '-1';
        $data['token'] = $token;
        $this->post('/provider/ka', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'status' => GameNotAllowedException::class . " => game-not-allowed",
                'statusCode' => 5,
                'userMessage' => 'game-not-allowed',
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
        $data = $this->requestData($active, $game);

        $data['token'] = $token;

        $this->post('/provider/ka', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'playerId' => $active->player_id,
                'sessionId' => $data['sessionId'],
                "status" => "success",
                "statusCode" => 0,
                'balance' => floor($wallet->money * 100),
                'balanceSequence' => $wallet->version,
            ]);
    }
}
