<?php
use App\Models\MemberWallet;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Provider\Allbet\AllbetBase;

class AllbetRevokeTest extends AllbetBase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    public function testRevoke()
    {
        $member = $this->getMember();
        $wallet = MemberWallet::findOrError($member->id);

        $data = [
            "amount" => 200,
            "client" => "gd_agmem01",
            "currency" => "CNY",
            "details" => [
                [
                    "amount" => 50,
                    "betNum" => 2993911200824864
                ],
                [
                    "amount" => 50,
                    "betNum" => 2993911202997013
                ],
                [
                    "amount" => 100,
                    "betNum" => 2993911201603405
                ]
            ],
            "tranId" => 1144518870011387904,
            "transferType" => 11
        ];

        $this->post('/provider/allbet/transfer', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'balance' => $wallet->money,
                'error_code' => 0,
                'message' => '',
            ]);
    }
}
