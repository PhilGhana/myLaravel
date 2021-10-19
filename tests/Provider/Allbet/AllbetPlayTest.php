<?php
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\MemberWallet;
use App\Models\Game;
use App\Models\ClubRankConfig;
use Tests\Provider\Allbet\AllbetBase;

class AllbetPlayTest extends AllbetBase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    public function testGetBalance()
    {
        $member = $this->getMember();
        $game = Game::where('platform_id', 6)->first();
        $wallet = MemberWallet::findOrError($member->id);
        $active = $this->getActive($member, $game);

        $this->post("/provider/allbet/get_balance/{$active->player_id}")
            ->assertStatus(200)
            ->assertExactJson([
                'balance' => $wallet->money,
                'error_code' => 0,
                'message' => '',
            ]);
    }

    public function testBet()
    {
        $member = $this->getMember();
        $game = Game::where('platform_id', 6)->first();
        $wallet = MemberWallet::findOrError($member->id);

        $data = [
            "amount" => 50,
            "client" => "gd_agmem01",
            "currency" => "CNY",
            "details" => [
                [
                    "amount" => 50,
                    "betNum" => 2996308090610300
                ],
            ],
            "tranId" => 1145524157983928320,
            "transferType" => 10
        ];

        /** @var MemberWallet $wallet */
        $wallet = MemberWallet::find($member->id);

        /** @var ClubRankConfig $cbconf */
        $cbconf = ClubRankConfig::where('club_id', $member->club_id)
            ->where('club_rank_id', $member->club_rank_id)
            ->where('game_id', $game->id)
            ->first();

        $money = $wallet->money - 50;

        $res = $this->post('/provider/allbet/transfer', $data);

        $res->assertStatus(200)
            ->assertExactJson([
                'balance' => $money,
                'error_code' => 0,
                'message' => '',
            ]);
    }
}
