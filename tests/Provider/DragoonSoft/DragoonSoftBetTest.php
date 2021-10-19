<?php
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\MemberWallet;
use Tests\Provider\DragoonSoft\DragoonSoftBase;
use App\Models\Game;
use App\Models\ClubRankConfig;
use Illuminate\Support\Facades\Log;

class DragoonSoftBetTest extends DragoonSoftBase
{
    public function getTransactionId()
    {
        return md5(microtime());
    }
    public function bet($tid, $betAmount)
    {
        $member = $this->getMember();
        $game = $this->getGame();
        $active = $this->getActive($member, $game);
        $data = $this->requestData($active);
        $data['trans_id'] = $tid;
        $data['amount'] = $betAmount;


        $wallet = MemberWallet::findOrError($member->id);
        $amount = $wallet->money - $betAmount;

        $res = $this->post('/provider/DragoonSoft/api/wallet/bet', $data);

        $res->assertStatus(200)
            ->assertExactJson([
                'trans_id' => $tid,
                "balance" => floor($amount * 100) / 100,
                "status" => 1,
            ]);
    }

    public function testPayout()
    {
        $member = $this->getMember();
        $game = $this->getGame();
        $active = $this->getActive($member, $game);
        $betAmount = 0.5;
        $tid = $this->getTransactionId();

        $wallet = MemberWallet::findOrError($member->id);

        $this->bet($tid, $betAmount);

        $win = 20;
        $data = $this->requestData($active);
        $data['trans_id'] = $tid;
        $data['amount'] = $win;
        $data['record'] = json_encode([
            'bet_at' => 1561457180178696700,
            'finish_at' => 1561457180178696700,
            'member_id' => "5d11cdad4774b30001658e26",
            'game_id' => "3001",
            'serial' => "64004",
            'game_hall' => 54,
            'round_id' => 64004,
            'bet_amount' => $betAmount * 1000,
            'payout_amount' => $win * 1000,
            'valid_amount' => $betAmount * 1000,
            'detail' => null,
            'status' => 1,
            'fee_amount' => 0,
            'jp_amount' => 0,
            'win_loss_amount' => -8500,
            'real_bet_amount' => 2500,
            'is_ai' => false,
            'jp_fee_amount' => 0
        ]);


        /** @var ClubRankConfig $cbconf */
        $cbconf = ClubRankConfig::where('club_id', $member->club_id)
            ->where('club_rank_id', $member->club_rank_id)
            ->where('game_id', $game->id)
            ->first();

        $amount = $wallet->money - $betAmount + $win +  $cbconf->getWaterAmount($betAmount);

        $res = $this->post('/provider/DragoonSoft/api/wallet/payout', $data);

        $res->assertStatus(200)
            ->assertExactJson([
                'trans_id' => $tid,
                "balance" => floor($amount * 100) / 100,
                "status" => 1,
            ]);
    }

    public function testCancel()
    {
        $member = $this->getMember();
        $game = $this->getGame();
        $active = $this->getActive($member, $game);
        $betAmount = 20;
        $tid = $this->getTransactionId();

        $wallet = MemberWallet::findOrError($member->id);

        $this->bet($tid, $betAmount);

        $data = $this->requestData($active);
        $data['trans_id'] = $tid;

        $amount = $wallet->money;

        $res = $this->post('/provider/DragoonSoft/api/wallet/cancel', $data);

        $res->assertStatus(200)
            ->assertExactJson([
                'trans_id' => $tid,
                "balance" => floor($amount * 100) / 100,
                "status" => 1,
            ]);
    }
}
