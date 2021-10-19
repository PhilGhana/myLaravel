<?php
use App\Models\MemberWallet;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Provider\WM\WMBase;

class WMRevokeTest extends WMBase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    public function testRevoke()
    {
        $game = $this->getGame();
        $member = $this->getMember();
        $wallet = MemberWallet::findOrError($member->id);
        $dealid = time();
        $date = date('Y-m-d H:i:s');

        $data = [
            "cmd" => "TimeoutBetReturn",
            "user" => "_agmem01",
            "money" => "-10.0000",
            "signature" => "cb4b46dcf0d05376b38a3acf38aebb6d",
            "requestDate" => "2019-06-21 10:54:24",
            "gtype" => "101",
            "dealid" => $dealid,
            "type" => "101_112193923_54_2",
            "betdetail" => [
                "Tie" => "-10"
            ],
            "gameno" => "101_112194657_62",
            "code" => "2"
        ];

        $this->post('/provider/wm', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'errorCode' => 0,
                'errorMessage' => '',
                'result' => [
                    'cash' => $wallet->money,
                    'dealid' => $dealid,
                    'money' => '-10.0000',
                    'responseDate' => $date,
                ],
            ]);
    }
}
