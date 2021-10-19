<?php
use App\Models\MemberWallet;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Provider\Ka\KaBase;
use ApiGameProvider\Base\Exceptions\SecurityVerificationFailedException;

class KaRevokeTest extends KaBase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    public function testInvalidToken()
    {
        $game = $this->getGame();
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $data = $this->requestData($active, $game);

        $data["transactionId"] = "ea3dae1fa2d3408fa9458e2e8b2e12c8" . time();
        $data["round"] = 0;
        $data["timestamp"] = 1559712645866;
        $data["sessionId"] = "3172e830ba674b6bbaa83a1b74bd9e19";
        $data["currency"] = "CNY";
        $data["action"] = "revoke";
        $data["gameId"] = "SuperShot";
        $data["playerIp"] = "36.234.140.16";
        $data["partnerPlayerId"] = "68c4be3db41f86168ae5120a0eff39f6";

        $this->call('POST', '/provider/ka?hash=jabdeiksiepwie', $data, [], [], [], json_encode($data))
            ->assertStatus(200)
            ->assertExactJson([
                'status' => SecurityVerificationFailedException::class . ' => ',
                'statusCode' => 3,
                'userMessage' => '',
            ]);
    }
}
