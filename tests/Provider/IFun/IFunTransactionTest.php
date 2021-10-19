<?php
use Tests\Provider\IFun\IFunBase;
use App\Models\MemberWallet;
use App\Models\LogMemberWallet;
use App\Models\ClubRankConfig;

class IFunTransactionTest extends IFunBase
{

    public function testPayin()
    {
        $id = md5(microtime());
        $amount = 12;
        $game = $this->getGame();
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $data = $this->getHashData([
            'partner_id' => $this->partnerId,
            'username' => $active->player_id,
            'currency' => 'MYR',
            'ref_id' => $id,
            'amount' => $amount,
        ]);
        $wallet = MemberWallet::findOrError($member->id);

        $res = $this->post('/provider/ifun/payin', $data);

        $tid = LogMemberWallet::where('member_id', $member->id)->max('id');

        $res->assertStatus(200)
            ->assertExactJson([
                'error' => 0,
                'message' => 'Success',
                'balance' => intval(($wallet->money + $amount) * 100) / 100,
                'trans_id' => "dev-0-{$tid}",
            ]);
    }

    public function testPayout()
    {
        $id = md5(microtime());
        $amount = 18;
        $game = $this->getGame();
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $data = $this->getHashData([
            'partner_id' => $this->partnerId,
            'username' => $active->player_id,
            'currency' => 'MYR',
            'ref_id' => $id,
            'amount' => $amount,
        ]);

        $wallet = MemberWallet::findOrError($member->id);

        $res = $this->post('/provider/ifun/payout', $data);

        $tid = LogMemberWallet::where('member_id', $member->id)->max('id');

        $res->assertStatus(200)
            ->assertExactJson([
                'error' => 0,
                'message' => 'Success',
                'balance' => intval(($wallet->money - $amount) * 100) / 100,
                'trans_id' => "dev-0-{$tid}",
            ]);
    }


}
