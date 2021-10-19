<?php
use Tests\Provider\BNG\BngBase;
use App\Models\MemberPlatformActive;
use App\Models\Member;
use App\Models\MemberWallet;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Game;
use App\Models\ClubRankConfig;
use Illuminate\Support\Facades\Log;

class BngRollbackTest extends BngBase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    protected function bet(Member $member, Game $game, MemberPlatformActive $active, $betAmount)
    {
        $sendAt = new DateTime();
        $now = time();
        $wallet = $member->wallet;
        if ($wallet->money < 100) {
            $wallet->money += 100;
            $wallet->save();
        }

        $winAmount = 0;
        $data = [
            'args' => [
                'bet' => $betAmount,
                'tag' => null,
                'round_id' => $now,
                'round_started' => true,
                'round_finished' => true,
                'player' => [
                    'mode' => "REAL",
                    'currency' => "CNY",
                    'id' => $active->player_id,
                    'is_test' => false
                ],
                'win' => $winAmount,
            ],
            'sent_at' => $sendAt->format('c'),
            'uid' => $now,
            'provider_id' => "1",
            'game_name' => "12_animals_bng",
            'session' => "cfe4ea6d1102463aabd6577b39d8cf99",
            'provider_name' => "booongo",
            'name' => "transaction",
            'c_at' => $sendAt->format('c'),
            'token' => "8cb473b1ad33a4644f9df786addfd0d4-1558338570",
            'game_id' => $game->code,
        ];


        $response = $this->post('/provider/bng', $data)
            ->assertStatus(200);


        /** @var ClubRankConfig $cbconf */
        $cbconf = ClubRankConfig::where('club_id', $member->club_id)
            ->where('club_rank_id', $member->club_rank_id)
            ->where('game_id', $game->id)
            ->first();

        $money = $wallet->money - $betAmount + $winAmount + ($cbconf->getWaterAmount($betAmount));
        $wallet = $wallet->fresh();
        $response->assertExactJson([
            'uid' => $now,
            'balance' => [
                'value' => strval($money),
                'version' => $wallet->version,
            ]
        ]);
        return $now;
    }

    public function xtestRollbackNotFound()
    {

        $platform = $this->platform;
        $game = $this->getGame();
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $sendAt = new DateTime();
        $now = time() . '-2';
        $betAmount = 20;

        $wallet = MemberWallet::findOrError($member->id);
        $sourceAmount = $wallet->money;

        # 建立一筆資料
        $uid = $now;

        $data = [
            'name' => 'rollback',
            'uid' => $now,
            'token' => "{$now}-token",
            'session' => "{$now}-session",
            "game_id" => $game->code,
            "game_name" => $game->name_en,
            "provider_id" => 1,
            "provider_name" => "test",
            "c_at" => $sendAt->format('c'),
            "sent_at" => $sendAt->format('c'),
            'args' => [
                'transaction_uid' => $uid,
                'bet' => null,
                'win' => null,
                'round_started' => true,
                'round_finished' => true,
                'round_id' => 1,
                'player' => [
                    'id' => $active->player_id,
                    'currency' => $platform->currency,
                    'mode' => "REAL",
                    'is_test' => false
                ],
                'tag' => ""
            ]
        ];


        /** @var ClubRankConfig $cbconf */
        $cbconf = ClubRankConfig::where('club_id', $member->club_id)
            ->where('club_rank_id', $member->club_rank_id)
            ->where('game_id', $game->id)
            ->first();

        $wallet = MemberWallet::findOrError($member->id);

        $response = $this->post('/provider/bng', $data)
            ->assertStatus(200);

        $wallet = $wallet->fresh();

        $response->assertExactJson([
            'uid' => $now,
            'balance' => [
                'value' => strval($sourceAmount),
                'version' => $wallet->version,
            ]
        ]);
    }

    public function testRollbackSuccess()
    {

        $platform = $this->platform;
        $game = $this->getGame();
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $sendAt = new DateTime();
        $now = time() . '-1';
        $betAmount = 5;

        $wallet = MemberWallet::findOrError($member->id);
        $sourceAmount = $wallet->money;

        # 建立一筆資料
        $uid = $this->bet($member, $game, $active, $betAmount);

        $data = [
            'name' => 'rollback',
            'uid' => $now,
            'token' => "{$now}-token",
            'session' => "{$now}-session",
            "game_id" => $game->code,
            "game_name" => $game->name_en,
            "provider_id" => 1,
            "provider_name" => "test",
            "c_at" => $sendAt->format('c'),
            "sent_at" => $sendAt->format('c'),
            'args' => [
                'transaction_uid' => $uid,
                'bet' => null,
                'win' => null,
                'round_started' => true,
                'round_finished' => true,
                'round_id' => 1,
                'player' => [
                    'id' => $active->player_id,
                    'currency' => $platform->currency,
                    'mode' => "REAL",
                    'is_test' => false
                ],
                'tag' => ""
            ]
        ];

        $wallet = MemberWallet::findOrError($member->id);

        $response = $this->post('/provider/bng', $data)
            ->assertStatus(200);

        $wallet = $wallet->fresh();

        $response->assertExactJson([
            'uid' => $now,
            'balance' => [
                'value' => strval($sourceAmount),
                'version' => $wallet->version,
            ]
        ]);
    }
}
