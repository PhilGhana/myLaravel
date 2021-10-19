<?php
namespace Tests\Provider\BNG;

use Tests\TestCase;
use App\Models\GamePlatform;
use App\Models\Game;
use App\Models\GameType;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\MemberPlatformActive;
use App\Models\Member;
use GuzzleHttp\Client;

class BngBase extends TestCase
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

    public function setUp()
    {
        parent::setUp();
        $this->platform = GamePlatform::where('key', 'bng')->first();
    }

    /**
     * Undocumented function
     *
     * @return Member
     */
    public function getMember () {
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
}
