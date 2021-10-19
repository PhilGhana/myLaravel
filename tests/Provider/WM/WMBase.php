<?php
namespace Tests\Provider\WM;

use Tests\TestCase;
use App\Models\GamePlatform;
use App\Models\Game;
use App\Models\GameType;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\MemberPlatformActive;
use App\Models\Member;

class WMBase extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    /**
     * Undocumented variable
     *
     * @var GamePlatform
     */
    protected $platform;

    public function setUp()
    {
        parent::setUp();
        $this->platform = GamePlatform::where('key', 'wm')->first();
    }

    public function requestData(MemberPlatformActive $active, Game $game)
    {
        $time = round(microtime(true) * 1000);

        return [
            "action" => "login",
            'partnerPlayerId' => $active->player_id,
            'playerId' => $active->player_id,
            "sessionId" => "session-$time",
            "timestamp" => $time,
            'gameId' => $game->code,
            'currency' => 'CNY',
            'token' => '',
            'operatorName' => 'xxx',
            'playerIp' => '127.0.0.1',
        ];
    }

    public function post($uri, array $data = [], array $headers = [])
    {
        $_SERVER['REQUEST_URI'] = $uri;
        return $this->call('POST', $uri, $data, [], [], [], json_encode($data));
    }

    /**
     * Undocumented function
     *
     * @return Member
     */
    public function getMember()
    {
        /** @var Member $member */
        $member = Member::where('account', 'agmem01')->first();
        return $member;
    }

    /**
     * Undocumented function
     *
     * @param Member $member
     * @param Game $game
     * @return MemberPlatformActive
     */
    protected function getActive(Member $member, Game $game)
    {
        /** @var MemberPlatformActive $active */
        $active = MemberPlatformActive::where('platform_id', $game->platform_id)
            ->where('member_id', $member->id)
            ->first();

        if (!$active) {
            $platform = GamePlatform::findOrError($game->platform_id);
            $active = new MemberPlatformActive();
            $active->platform_id = $game->platform_id;
            $active->member_id = $member->id;
            $active->generatePlayerId($platform->generatorMemberUsername($member->account), !$platform->use_password);
            $active->save();
        }
        return $active;
    }


    protected function getGame()
    {
        $game = Game::where('platform_id', $this->platform->id)->first();
        if (!$game) {
            $game = new Game();
            $game->platform_id = $this->platform->id;
            $game->type = GameType::TYPE_SLOT;
            $game->code = "123";
            $game->code_mobile = "123";
            $game->name_en = 'test';
            $game->name_jp = 'test';
            $game->name_zh_cn = 'test';
            $game->name_zh_tw = 'test';
            $game->enabled = 1;
            $game->maintain = 0;
            $game->saveOrError();
        }
        return $game;
    }
}
