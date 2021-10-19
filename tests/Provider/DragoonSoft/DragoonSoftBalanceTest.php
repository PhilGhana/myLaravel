<?php
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\MemberWallet;
use Tests\Provider\DragoonSoft\DragoonSoftBase;
use App\Models\Game;
use App\Models\ClubRankConfig;
use App\Services\Provider\AccessService;

class DragoonSoftBalanceTest extends DragoonSoftBase
{

    public function testGetBalance()
    {
        $member = $this->getMember();
        $game = $this->getGame();
        $active = $this->getActive($member, $game);
        $data['agent'] = 'IS880002UAT';
        $data['account'] = $active->player_id;

        $res = $this->post('/provider/DragoonSoft/api/wallet/balance', $data);

        /** @var MemberWallet $wallet */
        $wallet = MemberWallet::find($member->id);
        $res->assertStatus(200)
            ->assertExactJson([
                "balance" => floor(intval($wallet->money * 100)) / 100,
                "status" => 1,
            ]);
    }

}
