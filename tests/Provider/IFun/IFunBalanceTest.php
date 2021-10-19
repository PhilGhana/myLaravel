<?php
use Tests\Provider\IFun\IFunBase;
use App\Models\MemberWallet;

class IFunBalanceTest extends IFunBase
{

    public function testInvalidPartner()
    {

        $game = $this->getGame();
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $data = $this->getHashData([
            'partner_id' => 'abcde',
            'username' => $active->player_id,
        ]);

        $this->post('/provider/ifun/balance', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'error' => 2,
                'message' => 'Unauthorized access'
            ]);
    }
    public function testInvalidHash()
    {
        $game = $this->getGame();
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $data = $this->getHashData([
            'partner_id' => $this->partnerId,
            'username' => $active->player_id,
        ]);
        $data['hash'] = 'invalid hash';

        $this->post('/provider/ifun/balance', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'error' => 2,
                'message' => 'Unauthorized access'
            ]);
    }

    public function testBalance()
    {
        $game = $this->getGame();
        $member = $this->getMember();
        $active = $this->getActive($member, $game);
        $data = $this->getHashData([
            'partner_id' => $this->partnerId,
            'username' => $active->player_id,
        ]);
        $wallet = MemberWallet::findOrError($member->id);

        $this->post('/provider/ifun/balance', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'error' => 0,
                'message' => 'Success',
                'username' => $active->player_id,
                'balance' => intval($wallet->money * 100) / 100,
            ]);
    }
}
