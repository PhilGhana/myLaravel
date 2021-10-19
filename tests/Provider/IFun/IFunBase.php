<?php
namespace Tests\Provider\IFun;

use Tests\TestCase;
use App\Models\GamePlatform;
use App\Models\Game;
use App\Models\GameType;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\MemberPlatformActive;
use App\Models\Member;
use GuzzleHttp\Client;
use App\Services\Provider\AccessService;

class IFunBase extends TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    /**
     * Undocumented variable
     *
     * @var GamePlatform
     */
    protected $platform;

    /**
     * Undocumented variable
     *
     * @var Client
     */
    protected $client;

    protected $partnerId;
    protected $secretKey;

    public function setUp()
    {
        parent::setUp();
        $this->platform = GamePlatform::where('key', 'ifun')->first();
        $setting = json_decode($this->platform->setting);
        $this->partnerId = $setting->PARTNER_ID;
        $this->secretKey = $setting->SECRET_KEY;
    }

    /**
     * Undocumented function
     *
     * @return Member
     */
    public function getMember()
    {
        /** @var Member $member */
        $member = Member::where('account', 'agmem03')->first();
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
            $active->enabled = 1;
            $active->generatePlayerId($platform->generatorMemberUsername($member->account), !$platform->use_password);
            $active->save();
        }

        $access = new AccessService($active);
        $access->set('game-code', $game->code);

        return $active;
    }


    protected function getGame()
    {

        $game = Game::where('platform_id', $this->platform->id)->first();
        // $game = Game::find(3);
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

    public function post($uri, array $data = [], array $headers = [])
    {
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['REDIRECT_URL'] = $uri;
        return $this->call('POST', $uri, $data, [], [], [], json_encode($data));
    }

    protected function getHashData($data)
    {
        ksort($data);
        $data['hash'] = md5(http_build_query($data) . $this->secretKey);
        return $data;
    }
}
