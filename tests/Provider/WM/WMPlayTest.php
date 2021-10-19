<?php
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Provider\WM\WMBase;
use App\Models\MemberWallet;
use App\Models\Game;
use App\Models\ClubRankConfig;

class WMPlayTest extends WMBase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    public function testGetBalance()
    {
        $member = $this->getMember();
        $game = Game::where('platform_id', 5)->first();
        $wallet = MemberWallet::findOrError($member->id);
        $active = $this->getActive($member, $game);

        $data = [
            "cmd" => "CallBalance",
            "user" => "_agmem01",
            "signature" => "cb4b46dcf0d05376b38a3acf38aebb6d",
            "requestDate" => "2019-06-25 13:52:13"
        ];

        $this->post('/provider/wm', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'errorCode' => 0,
                'errorMessage' => '',
                'result' => [
                    'money' => "{$wallet->money}",
                    'responseDate' => date('Y-m-d H:i:s'),
                    'user' => $active->player_id,
                ],
            ]);
    }

    public function testBet()
    {
        $member = $this->getMember();
        $game = Game::where('platform_id', 5)->where('code', '101')->first();
        $wallet = MemberWallet::findOrError($member->id);
        $betAmount = -60;
        $date = date('Y-m-d H:i:s');
        $time = time();
        $mid = "101_{$time}_37";

        $data = [
            "cmd" => "PointInout",
            "user" => "_agmem01",
            "money" => "{$betAmount}",
            "signature" => "cb4b46dcf0d05376b38a3acf38aebb6d",
            "requestDate" => $date,
            "gtype" => "101",
            "dealid" => $time,
            "type" => "101_{$time}_37_2",
            "betdetail" => [
                "Banker" => "-10",
                "Player" => "-30",
                "Tie" => "-10",
                "BPair" => "-10"
            ],
            "gameno" => $mid,
            "code" => "2"
        ];

        /** @var MemberWallet $wallet */
        $wallet = MemberWallet::find($member->id);

        /** @var ClubRankConfig $cbconf */
        $cbconf = ClubRankConfig::where('club_id', $member->club_id)
            ->where('club_rank_id', $member->club_rank_id)
            ->where('game_id', $game->id)
            ->first();

        $money = $wallet->money + $betAmount - ($cbconf->getWaterAmount($betAmount));

        $res = $this->post('/provider/wm', $data);
        $resTime = date('Y-m-d H:i:s');

        $res->assertStatus(200)
            ->assertExactJson([
                'errorCode' => 0,
                'errorMessage' => '',
                'result' => [
                    'cash' => "{$money}",
                    'dealid' => $time,
                    'money' => "{$betAmount}",
                    'responseDate' => $resTime,
                ],
            ]);
    }

    public function testComplete()
    {
        $data = [
            "cmd" => "SendMemberReport",
            "signature" => "cb4b46dcf0d05376b38a3acf38aebb6d",
            "requestDate" => "2019-06-25 11:17:07",
            "result" => [
                [
                    "user" => "_agmem01",
                    "betId" => "12669444",
                    "betTime" => "2019-06-25 11:13:47",
                    "bet" => "20.0000",
                    "validbet" => "0.0000",
                    "water" => "0.0000",
                    "result" => "-20.0000",
                    "betResult" => "Banker",
                    "waterbet" => "0.0000",
                    "winLoss" => "-20.0000",
                    "ip" => "192.168.30.1",
                    "gid" => "101",
                    "event" => "112194851",
                    "eventChild" => "63",
                    "round" => "112194851",
                    "subround" => "63",
                    "tableId" => "1",
                    "betwalletid" => "75462,75463,",
                    "resultwalletid" => "-1",
                    "commission" => "0",
                    "reset" => "N",
                    "settime" => "2019-06-25 11:14:01",
                    "gameResult" => "Banker:\u2666Q\u2660Q\u2663QPlayer:\u26664\u26663",
                    "gname" => "Baccarat"
                ],
                [
                    "user" => "_agmem01",
                    "betId" => "12669445",
                    "betTime" => "2019-06-25 11:13:47",
                    "bet" => "30.0000",
                    "validbet" => "10.0000",
                    "water" => "0.0000",
                    "result" => "30.0000",
                    "betResult" => "Player",
                    "waterbet" => "10.0000",
                    "winLoss" => "30.0000",
                    "ip" => "192.168.30.1",
                    "gid" => "101",
                    "event" => "112194851",
                    "eventChild" => "63",
                    "round" => "112194851",
                    "subround" => "63",
                    "tableId" => "1",
                    "betwalletid" => "75462,75463,",
                    "resultwalletid" => "75464",
                    "commission" => "0",
                    "reset" => "N",
                    "settime" => "2019-06-25 11:14:01",
                    "gameResult" => "Banker:\u2666Q\u2660Q\u2663QPlayer:\u26664\u26663",
                    "gname" => "Baccarat"
                ],
                [
                    "user" => "_agmem01",
                    "betId" => "12669446",
                    "betTime" => "2019-06-25 11:13:47",
                    "bet" => "20.0000",
                    "validbet" => "20.0000",
                    "water" => "0.0000",
                    "result" => "-20.0000",
                    "betResult" => "Tie",
                    "waterbet" => "20.0000",
                    "winLoss" => "-20.0000",
                    "ip" => "192.168.30.1",
                    "gid" => "101",
                    "event" => "112194851",
                    "eventChild" => "63",
                    "round" => "112194851",
                    "subround" => "63",
                    "tableId" => "1",
                    "betwalletid" => "75462,75463,",
                    "resultwalletid" => "-1",
                    "commission" => "0",
                    "reset" => "N",
                    "settime" => "2019-06-25 11:14:01",
                    "gameResult" => "Banker:\u2666Q\u2660Q\u2663QPlayer:\u26664\u26663",
                    "gname" => "Baccarat"
                ]
            ]
        ];

        $this->post('/provider/wm', $data)
            ->assertStatus(200)
            ->assertExactJson([
                'errorCode' => 0,
                'errorMessage' => '',
            ]);
    }
}
