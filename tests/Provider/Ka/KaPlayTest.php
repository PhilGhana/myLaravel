<?php
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\Provider\Ka\KaBase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\MemberWallet;
use App\Models\ClubRankConfig;

class KaPlayTest extends KaBase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    public function play()
    {
        $game = $this->getGame();
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $data = $this->requestData($active, $game);
        $betAmount = 10;

        $data["transactionId"] = "ea3dae1fa2d3408fa9458e2e8b2e12c8" . microtime(true);
        $data["round"] = 0;
        $data["timestamp"] = 1559712645866;
        $data["sessionId"] = "3172e830ba674b6bbaa83a1b74bd9e19";
        $data["currency"] = "CNY";
        $data["action"] = "play";
        $data["gameId"] = "SuperShot";
        $data["playerIp"] = "36.234.140.16";
        $data["partnerPlayerId"] = "68c4be3db41f86168ae5120a0eff39f6";
        $data['betAmount'] = $betAmount * 100;
        $data['freeGames'] = false;

        /** @var MemberWallet $wallet */
        $wallet = MemberWallet::find($member->id);
        $res = $this->post('/provider/ka', $data);

        /** @var ClubRankConfig $cbconf */
        $cbconf = ClubRankConfig::where('club_id', $member->club_id)
            ->where('club_rank_id', $member->club_rank_id)
            ->where('game_id', $game->id)
            ->first();

        $money = $wallet->money - $betAmount + ($cbconf->getWaterAmount($betAmount));

        $wallet = $wallet->fresh();

        $res->assertStatus(200)
            ->assertExactJson([
                "balance" => floor($money * 100),
                "balanceSequence" => $wallet->version,
                "status" => "success",
                "statusCode" => 0
            ]);
        return $data["transactionId"];
    }

    public function testPlay() {
        $this->play();
    }


    public function testCredit()
    {
        $game = $this->getGame();
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $data = $this->requestData($active, $game);
        $amount = 15;
        $data["transactionId"] = $this->play();
        $data["round"] = 0;
        $data["timestamp"] = 1559712645866;
        $data["sessionId"] = "3172e830ba674b6bbaa83a1b74bd9e19";
        $data["currency"] = "CNY";
        $data["action"] = "credit";
        $data["gameId"] = "SuperShot";
        $data["playerIp"] = "36.234.140.16";
        $data["partnerPlayerId"] = "68c4be3db41f86168ae5120a0eff39f6";
        $data['amount'] = $amount * 100;
        $data['freeGames'] = false;
        $data['creditIndex'] = time();

        /** @var MemberWallet $wallet */
        $wallet = MemberWallet::find($member->id);

        $res = $this->post('/provider/ka', $data);
        $money =  ($wallet->money + $amount) * 100;

        $wallet = $wallet->fresh();

        $res->assertStatus(200)
            ->assertExactJson([
                "balance" => floor($money),
                "balanceSequence" => $wallet->version,
                "status" => "success",
                "statusCode" => 0
            ]);
    }

}
