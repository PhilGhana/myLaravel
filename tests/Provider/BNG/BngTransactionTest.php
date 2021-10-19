<?php
use Tests\Provider\BNG\BngBase;
use App\Models\MemberPlatformActive;
use App\Models\Member;
use App\Models\MemberWallet;
use App\Models\ClubRankConfig;
use Illuminate\Support\Facades\Log;

class BngTransactionTest extends BngBase
{

    private $testHidden = [
        'testBetFundsExceed' => 1,
        'testBetSuccess' => 1,
        'testTwoStepBetSuccess' => 1,
        'testBetHasError' => 0
    ];

    public function testBetFundsExceed()
    {
        if ($this->testHidden['testBetFundsExceed'] ?? 0) {
            return $this->assertTrue(true);
        }

        $game = $this->getGame();
        $sendAt = new DateTime();
        $now = microtime(true);
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $wallet = $member->wallet;

        $data = [
            'args' => [
                'bet' => $wallet->money + 1,
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
                'win' => "0"
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

        $this->post('/provider/bng', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'uid' => $now,
                'error' => [
                    'code' => 'FUNDS_EXCEED',
                    'message' => 'provider.found-exceed',
                ]
            ]);
    }

    public function testBetHasError ()
    {
        $game = $this->getGame();
        $sendAt = new DateTime();
        $now = microtime(true);
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $wallet = $member->wallet;

        $data = [
            'args' => [
                'bet' => $wallet->money + 1,
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
                'win' => "0"
            ],
            'provider_id' => "1",
            'token' => "c4d97bc1ad68e905980aaa71fbe61098-1561426296",
            'uid' => $now,
            'sent_at' => $sendAt->format('c'),
            'name' => "transaction",
            'c_at' => $sendAt->format('c'),
            'session' => "68f18b92ebeb4a178444af86ca0ea322",
            'provider_name' => "booongo",
            'game_name' => "dragon_pearls_bng",
            'game_id' => "151"
        ];

        $this->post('/provider/bng', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'uid' => $now,
                'error' => [
                    'code' => 'FUNDS_EXCEED',
                    'message' => 'provider.found-exceed',
                ]
            ]);
    }

    public function testBetSuccess()
    {

        if ($this->testHidden['testBetSuccess'] ?? 0) {
            return $this->assertTrue(true);
        }

        $game = $this->getGame();
        $sendAt = new DateTime();
        $now = microtime(true);
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $wallet = $member->wallet;
        if ($wallet->money < 100) {
            $wallet->money += 100;
            $wallet->save();
        }

        $betAmount = 15;
        $winAmount = 25;
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
        Log::info(print_r([$wallet->money, $betAmount, $winAmount, $cbconf->getWaterAmount($betAmount)], true));
        $money = $wallet->money - $betAmount + $winAmount + $cbconf->getWaterAmount($betAmount);
        $wallet = $wallet->fresh();
        $response->assertExactJson([
            'uid' => $now,
            'balance' => [
                'value' => strval($money),
                'version' => $wallet->version,
            ]
        ]);

        $now = microtime(true);
        $prize = 22;
        $data['args']['bet'] = null;
        $data['args']['win'] = $prize;
        $data['uid'] = $now;
        $response = $this->post('/provider/bng', $data)
            ->assertStatus(200);

        $money = $wallet->money + $prize;
        $wallet = $wallet->fresh();
        $response->assertExactJson([
            'uid' => $now,
            'balance' => [
                'value' => strval($money),
                'version' => $wallet->version,
            ]
        ]);
    }

    public function testTwoStepBetSuccess()
    {
        if ($this->testHidden['testTwoStepBetSuccess'] ?? 0) {
            return $this->assertTrue(true);
        }

        $game = $this->getGame();
        $sendAt = new DateTime();
        $now = microtime(true);
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $wallet = $member->wallet;
        if ($wallet->money < 100) {
            $wallet->money += 100;
            $wallet->save();
        }

        $betAmount = 15;
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
                'win' => null,
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
        $money = $wallet->money - $betAmount + ($cbconf->getWaterAmount($betAmount));
        $wallet = $wallet->fresh();
        $response->assertExactJson([
            'uid' => $now,
            'balance' => [
                'value' => strval($money),
                'version' => $wallet->version,
            ]
        ]);

        $now = microtime(true);
        $winAmount = 25;
        $data['args']['bet'] = null;
        $data['args']['win'] = $winAmount;
        $data['uid'] = $now;
        $response = $this->post('/provider/bng', $data)
            ->assertStatus(200);
        $money = $wallet->money + $winAmount;
        $wallet = $wallet->fresh();
        $response->assertExactJson([
            'uid' => $now,
            'balance' => [
                'value' => strval($money),
                'version' => $wallet->version,
            ]
        ]);
    }
}
