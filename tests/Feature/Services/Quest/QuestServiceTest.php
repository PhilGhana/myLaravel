<?php

namespace Tests\Feature\Services\Quest;

use Tests\TestCase;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\ClubRank;
use App\Models\Franchisee;
use App\Models\Member;
use App\Models\MemberQuestSort;
use App\Models\QuestGroup;
use App\Models\QuestReward;
use App\Models\Review\ReviewMemberDepositBank;
use App\Models\RewardDetail;
use App\Services\Quest\QuestService;
use Carbon\Carbon;

class QuestServiceTest extends TestCase
{
    use WithoutMiddleware;
    // use RefreshDatabase;
    use DatabaseTransactions;

    protected $franchisee;
    protected $clubRank;
    protected $member;
    protected $questService;

    public function setUp(): void
    {
        parent::setUp();

        $this->clubRank = factory(ClubRank::class)->create([
                'club_id' => 99,
            ]);
        $this->franchisee = factory(Franchisee::class)->create([
                'register_club_rank_id' => $this->clubRank->id,
            ]);
        $this->clubRank->franchisee_id = $this->franchisee->id;
        $this->clubRank->save();

        $this->member = factory(Member::class)->create([
                'franchisee_id' => $this->franchisee->id,
                'club_rank_id' => $this->clubRank->id,
                'club_id' => $this->clubRank->club_id,
            ]);

        $this->questService = new QuestService($this->member);

        // 先關閉所有任務
        $systemQuests = QuestReward::where('enabled', '=', true)
            ->get();
        $systemQuests->each(function ($item, $key) {
                $item->enabled = false;
                $item->save();
            });
    }

    /**
     * 固定型優惠任務
     */
    public function testCountRewardsWithFixedQuest()
    {
        $questData = [
            'quest_name' => 'testCountRewardsWithFixedQuest',
            'quest_type_id' => 3,
            'bet_amount' => 1000,
            'reward' => 250,
        ];
        $quest = $this->createQuest($questData);

        // 第一次投注，未達到流水量
        $betId = 1;
        $validAmount = 500;
        $countValidAmount = 0 + $validAmount;
        $gameTime = Carbon::now();
        $this->callCountRewards($betId, null, $validAmount, $gameTime);

        $this->assertDatabaseHas('reward_detail', [
                'quest_id' => $quest->id,
                'quest_type_id' => $quest->quest_type_id,
                'quest_type' => 'fixed',
                'member_id' => $this->member->id,
                'reward' => 0,
                'valid_amount' => $validAmount,
                'status' => RewardDetail::STATUS_PROCESSING,
                'bet_id' => json_encode([$betId]),
                'applied_at' => $gameTime,
            ]);

        // 第二次投注，達到流水量並更改記錄狀態
        $betId = 2;
        $validAmount = 500;
        $countValidAmount = $countValidAmount + $validAmount;
        $gameTime = Carbon::now();
        $this->callCountRewards($betId, null, $validAmount, $gameTime);

        $this->assertDatabaseHas('reward_detail', [
            'quest_id' => $quest->id,
            'quest_type_id' => $quest->quest_type_id,
            'quest_type' => 'fixed',
            'member_id' => $this->member->id,
            'reward' => $quest->reward,
            'valid_amount' => $countValidAmount,
            'status' => RewardDetail::STATUS_COMPLETED,
            'bet_id' => json_encode([1, 2]),
            'applied_at' => $gameTime,
        ]);
    }

    /**
     * 百分比型優惠任務
     */
    public function testCountRewardsWithPercentQuest()
    {
        $questData = [
                'quest_name' => 'testCountRewardsWithPercentQuest',
                'quest_type_id' => 2,
                'percent' => 0.05,
                'bet_amount' => 1000,
                'bet_amount_min' => 800,
                'reward_max' => 100,
            ];
        $quest = $this->createQuest($questData);

        // 第一次投注，未達到流水量
        $betId = 1;
        $validAmount = 500;
        $countValidAmount = 0 + $validAmount;
        $gameTime = Carbon::now();
        $this->callCountRewards($betId, null, $validAmount, $gameTime);

        $this->assertDatabaseHas('reward_detail', [
                'quest_id' => $quest->id,
                'quest_type_id' => $quest->quest_type_id,
                'quest_type' => 'percent',
                'member_id' => $this->member->id,
                'reward' => 0,
                'valid_amount' => $countValidAmount,
                'status' => RewardDetail::STATUS_PROCESSING,
                'bet_id' => json_encode([$betId]),
                'applied_at' => $gameTime,
            ]);

        // 第三次投注，達到流水量並更改記錄狀態
        $betId = 2;
        $validAmount = 50;
        $countValidAmount = $countValidAmount + $validAmount;
        $this->callCountRewards($betId, null, $validAmount, null);

        $betId = 3;
        $validAmount = 5000;
        $countValidAmount = $countValidAmount + $validAmount;
        $gameTime = Carbon::now();
        $this->callCountRewards($betId, null, $validAmount, $gameTime);

        $this->assertDatabaseHas('reward_detail', [
            'quest_id' => $quest->id,
            'quest_type_id' => $quest->quest_type_id,
            'quest_type' => 'percent',
            'member_id' => $this->member->id,
            'reward' => $quest->reward_max,
            'valid_amount' => $countValidAmount,
            'status' => RewardDetail::STATUS_COMPLETED,
            'bet_id' => json_encode([1, 2, 3]),
            'applied_at' => $gameTime,
        ]);
    }

    /**
     * 首儲優惠活動
     */
    public function testFirstDepositQuest()
    {
        $questData = [
            'quest_name' => 'testFirstDepositQuest',
            'quest_type_id' => 1,
            'extra' => 500, // 儲值金額
            'bet_amount' => 1000,
            'reward' => 500,
        ];
        $quest = $this->createQuest($questData);

        $this->assertDatabaseHas('quest_reward', [
            'quest_type_id' => $questData['quest_type_id'],
            'franchisee_id' => $this->franchisee->id,
            'club_rank_id' => $this->clubRank->id,
            'bet_amount' => $questData['bet_amount'],
            'reward' => $questData['reward'],
            'extra' => $questData['extra'],
        ]);

        // 新增一筆申請存款的紀錄，且通過審查的狀態
        $reviewDepositData = [
                'franchisee_id' => $this->franchisee->id,
                'member_id' => $this->member->id,
                'apply_amount' => 501,
                'real_amount' => 500,
                'status' => 'approved',
            ];
        $reviewDeposit = factory(ReviewMemberDepositBank::class)->create($reviewDepositData);

        // 新增一筆贈點紀錄
        $this->questService->checkAndUpdateDepositQuest($reviewDeposit->id, $reviewDeposit->real_amount);

        $this->assertDatabaseHas('reward_detail', [
            'quest_id' => $quest->id,
            'quest_type_id' => $quest->quest_type_id,
            'quest_type' => 'fixed',
            'member_id' => $this->member->id,
            'deposit_id' => $reviewDeposit->id,
            'reward' => $quest->reward,
            'valid_amount' => 0,
            'status' => RewardDetail::STATUS_COMPLETED,
        ]);

        // 流水量達到標準，則需贈點
        $betId = 1;
        $gameTime = Carbon::now();
        $this->callCountRewards($betId, null, $questData['bet_amount'], $gameTime);

        $this->assertDatabaseHas('reward_detail', [
            'quest_id' => $quest->id,
            'quest_type_id' => $quest->quest_type_id,
            'quest_type' => 'fixed',
            'member_id' => $this->member->id,
            'deposit_id' => $reviewDeposit->id,
            'reward' => $quest->reward,
            'valid_amount' => $questData['bet_amount'],
            'status' => RewardDetail::STATUS_COMPLETED,
            'bet_id' => json_encode([1]),
            'applied_at' => $gameTime,
        ]);
    }

    /**
     * 儲值活動優先於其他活動
     */
    public function testDepositQuestIsPriority()
    {
        $questData = [
            'quest_name' => 'testDepositQuestIsPriority#1',
            'quest_type_id' => 9,
            'extra' => 500, // 儲值金額
            'bet_amount' => 1000,
            'reward' => 500,
        ];
        $depositQuest = $this->createQuest($questData);

        $this->assertDatabaseHas('quest_reward', [
            'id' => $depositQuest->id,
            'quest_type_id' => $questData['quest_type_id'],
            'franchisee_id' => $this->franchisee->id,
            'club_rank_id' => $this->clubRank->id,
            'bet_amount' => $questData['bet_amount'],
            'reward' => $questData['reward'],
            'extra' => $questData['extra'],
        ]);

        $questData = [
            'quest_name' => 'testDepositQuestIsPriority#2',
            'quest_type_id' => 3,
            'bet_amount' => 1000,
            'reward' => 250,
            'order' => 5,
        ];
        $fixedQuest = $this->createQuest($questData);

        $this->assertDatabaseHas('quest_reward', [
            'id' => $fixedQuest->id,
            'quest_type_id' => $questData['quest_type_id'],
            'franchisee_id' => $this->franchisee->id,
            'club_rank_id' => $this->clubRank->id,
            'bet_amount' => $questData['bet_amount'],
            'reward' => $questData['reward'],
        ]);

        // 將儲值活動的排序往前
        factory(MemberQuestSort::class)->create([
            'member_id' => $this->member->id,
            'sort' =>json_encode([$depositQuest->id,  $fixedQuest->id]),
        ]);

        // 先觸發固定型優惠活動，未達到流水量
        $betId = 1;
        $firstValidAmount = 500;
        $gameTime = Carbon::now();
        $this->callCountRewards($betId, null, $firstValidAmount, $gameTime);

        $this->assertDatabaseHas('reward_detail', [
                'quest_id' => $fixedQuest->id,
                'quest_type_id' => $fixedQuest->quest_type_id,
                'quest_type' => 'fixed',
                'member_id' => $this->member->id,
                'reward' => 0,
                'valid_amount' => $firstValidAmount,
                'status' => RewardDetail::STATUS_PROCESSING,
                'bet_id' => json_encode([$betId]),
                'applied_at' => $gameTime,
            ]);

        // 新增一筆申請存款的紀錄，且通過審查的狀態
        $reviewDepositData = [
                'franchisee_id' => $this->franchisee->id,
                'member_id' => $this->member->id,
                'apply_amount' => 501,
                'real_amount' => 500,
                'status' => 'approved',
            ];
        $reviewDeposit = factory(ReviewMemberDepositBank::class)->create($reviewDepositData);

        // 新增一筆贈點紀錄，這時侯觸發儲值任務
        $this->questService->checkAndUpdateDepositQuest($reviewDeposit->id, $reviewDeposit->real_amount);

        $this->assertDatabaseHas('reward_detail', [
            'quest_id' => $depositQuest->id,
            'quest_type_id' => $depositQuest->quest_type_id,
            'quest_type' => 'fixed',
            'member_id' => $this->member->id,
            'deposit_id' => $reviewDeposit->id,
            'reward' => 0,
            'valid_amount' => 0,
            'status' => RewardDetail::STATUS_PROCESSING,
        ]);

        // 流水量達到標準，則需贈點
        $betId = 2;
        $secondValidAmount = 1200;
        $gameTime = Carbon::now();
        $this->callCountRewards($betId, null, $secondValidAmount, $gameTime);

        $this->assertDatabaseHas('reward_detail', [
            'quest_id' => $depositQuest->id,
            'quest_type_id' => $depositQuest->quest_type_id,
            'quest_type' => 'fixed',
            'member_id' => $this->member->id,
            'deposit_id' => $reviewDeposit->id,
            'reward' => $depositQuest->reward,
            'valid_amount' => $secondValidAmount,
            'status' => RewardDetail::STATUS_COMPLETED,
            'bet_id' => json_encode([2]),
            'applied_at' => $gameTime,
        ]);

        // 完成原本的活動
        $betId = 3;
        $finalValidAmount = 1200;
        $gameTime = Carbon::now();
        $this->callCountRewards($betId, null, $finalValidAmount, $gameTime);

        $this->assertDatabaseHas('reward_detail', [
                'quest_id' => $fixedQuest->id,
                'quest_type_id' => $fixedQuest->quest_type_id,
                'quest_type' => 'fixed',
                'member_id' => $this->member->id,
                'reward' => $fixedQuest->reward,
                'valid_amount' => ($finalValidAmount + $firstValidAmount),
                'status' => RewardDetail::STATUS_COMPLETED,
                'bet_id' => json_encode([1, 3]),
                'updated_at' => $gameTime,
            ]);
    }

    /**
     * 固定型儲值活動
     */
    public function testFixedDepositQuest()
    {
        $questData = [
            'quest_name' => 'testFixedDepositQuest',
            'quest_type_id' => 9,
            'extra' => 500, // 儲值金額
            'bet_amount' => 1000,
            'reward' => 500,
        ];
        $quest = $this->createQuest($questData);

        $this->assertDatabaseHas('quest_reward', [
            'quest_type_id' => $questData['quest_type_id'],
            'franchisee_id' => $this->franchisee->id,
            'club_rank_id' => $this->clubRank->id,
            'bet_amount' => $questData['bet_amount'],
            'reward' => $questData['reward'],
            'extra' => $questData['extra'],
        ]);

        // 新增一筆申請存款的紀錄，且通過審查的狀態
        $reviewDepositData = [
                'franchisee_id' => $this->franchisee->id,
                'member_id' => $this->member->id,
                'apply_amount' => 501,
                'real_amount' => 500,
                'status' => 'approved',
            ];
        $reviewDeposit = factory(ReviewMemberDepositBank::class)->create($reviewDepositData);

        // 新增一筆贈點紀錄
        $this->questService->checkAndUpdateDepositQuest($reviewDeposit->id, $reviewDeposit->real_amount);

        $this->assertDatabaseHas('reward_detail', [
            'quest_id' => $quest->id,
            'quest_type_id' => $quest->quest_type_id,
            'quest_type' => 'fixed',
            'member_id' => $this->member->id,
            'deposit_id' => $reviewDeposit->id,
            'reward' => 0,
            'valid_amount' => 0,
            'status' => RewardDetail::STATUS_PROCESSING,
        ]);

        // 流水量達到標準，則需贈點
        $betId = 1;
        $gameTime = Carbon::now();
        $this->callCountRewards($betId, null, $questData['bet_amount'], $gameTime);

        $this->assertDatabaseHas('reward_detail', [
            'quest_id' => $quest->id,
            'quest_type_id' => $quest->quest_type_id,
            'quest_type' => 'fixed',
            'member_id' => $this->member->id,
            'deposit_id' => $reviewDeposit->id,
            'reward' => $quest->reward,
            'valid_amount' => $questData['bet_amount'],
            'status' => RewardDetail::STATUS_COMPLETED,
            'bet_id' => json_encode([1]),
            'applied_at' => $gameTime,
        ]);
    }

    /**
     * 百分比型儲值活動
     */
    public function testPercentDepositQuest()
    {
        $questData = [
            'quest_name' => 'testFixedDepositQuest',
            'quest_type_id' => 10,
            'extra' => 200, // 最少儲值金額
            'percent' => 0.05,
            'bet_amount' => 2, // 流水倍率
            'reward_max' => 100,
        ];
        $quest = $this->createQuest($questData);

        $this->assertDatabaseHas('quest_reward', [
            'quest_type_id' => $questData['quest_type_id'],
            'franchisee_id' => $this->franchisee->id,
            'club_rank_id' => $this->clubRank->id,
            'extra' => $questData['extra'],
            'percent' => $questData['percent'],
            'bet_amount' => $questData['bet_amount'],
            'reward_max' => $questData['reward_max'],
        ]);

        // 新增一筆申請存款的紀錄，且通過審查的狀態
        $reviewDepositData = [
                'franchisee_id' => $this->franchisee->id,
                'member_id' => $this->member->id,
                'apply_amount' => 501,
                'real_amount' => 500,
                'status' => 'approved',
            ];
        $reviewDeposit = factory(ReviewMemberDepositBank::class)->create($reviewDepositData);

        // 新增一筆贈點紀錄
        $this->questService->checkAndUpdateDepositQuest($reviewDeposit->id, $reviewDeposit->real_amount);

        $this->assertDatabaseHas('reward_detail', [
            'quest_id' => $quest->id,
            'quest_type_id' => $quest->quest_type_id,
            'quest_type' => 'percent',
            'member_id' => $this->member->id,
            'deposit_id' => $reviewDeposit->id,
            'reward' => 0,
            'valid_amount' => 0,
            'status' => RewardDetail::STATUS_PROCESSING,
        ]);

         // 流水量達到標準，則需贈點
         $betId = 1;
         $validAmount = 1000;
         $gameTime = Carbon::now();
         $this->callCountRewards($betId, null, $validAmount, $gameTime);

        // 計算贈點、流水量
        $reward =  $reviewDeposit->real_amount * $quest->percent;
        $expectedAmount = $reward * $quest->bet_amount;
        $this->assertGreaterThanOrEqual($expectedAmount, $validAmount);

        $this->assertDatabaseHas('reward_detail', [
             'quest_id' => $quest->id,
             'quest_type_id' => $quest->quest_type_id,
             'quest_type' => 'percent',
             'member_id' => $this->member->id,
             'deposit_id' => $reviewDeposit->id,
             'reward' => $reward,
             'valid_amount' => $validAmount,
             'status' => RewardDetail::STATUS_COMPLETED,
             'bet_id' => json_encode([1]),
             'applied_at' => $gameTime,
         ]);
    }

    /**
     * 不分等級的固定型優惠任務
     */
    public function testAllClubRankWithFixedQuest()
    {
        $questData = [
            'quest_name' => 'testAllClubRankWithFixedQuest',
            'quest_type_id' => 3,
            'bet_amount' => 1000,
            'reward' => 250,
            'club_rank_id' => 'all',
        ];
        $quest = $this->createQuest($questData);

        // 投注，達到流水量並更改記錄狀態
        $betId = 2;
        $validAmount = 1500;
        $countValidAmount = 0 + $validAmount;
        $gameTime = Carbon::now();
        $this->callCountRewards($betId, null, $validAmount, $gameTime);

        $this->assertDatabaseHas('reward_detail', [
            'quest_id' => $quest->id,
            'quest_type_id' => $quest->quest_type_id,
            'quest_type' => 'fixed',
            'member_id' => $this->member->id,
            'reward' => $quest->reward,
            'valid_amount' => $countValidAmount,
            'status' => RewardDetail::STATUS_COMPLETED,
            'bet_id' => json_encode([2]),
            'applied_at' => $gameTime,
        ]);
    }

    /**
     * 階段式活動
     */
    public function testStageQuest()
    {
        $stages = [
            [
                'type' => 'fixed',
                'amount' => 500,
                'reward' => 50,
                'percent' => null,
                'rewardMax' => null,
            ],
            [
                'type' => 'percent',
                'amount' => 1000,
                'reward' => null,
                'percent' => 10,
                'rewardMax' => 70,
            ],
            [
                'type' => 'fixed',
                'amount' => 2500,
                'reward' => 150,
                'percent' => null,
                'rewardMax' => null,
            ],
        ];
        $questData = [
            'quest_name' => 'testStageQuest',
            'quest_type_id' => 12,
            'bet_amount' => 0,
            'stages' => json_encode($stages),
        ];
        $quest = $this->createQuest($questData);

        $this->assertDatabaseHas('quest_reward', [
            'quest_type_id' => $questData['quest_type_id'],
            'franchisee_id' => $this->franchisee->id,
            'club_rank_id' => $this->clubRank->id,
            'bet_amount' => $questData['bet_amount'],
            'stages' => $questData['stages'],
        ]);

        // 第一次投注，未達到流水量
        $betId = 1;
        $validAmount = 400;
        $gameTime = Carbon::now();
        $this->callCountRewards($betId, null, $validAmount, $gameTime);
        $appliedAt = $gameTime;
        $totalAmount = $validAmount;

        // 第二次投注，達到第一階段流水量
        $betId = 2;
        $validAmount = 500;
        $gameTime = Carbon::now();
        $this->callCountRewards($betId, null, $validAmount, $gameTime);
        $totalAmount = $totalAmount + $validAmount;

        $this->assertDatabaseHas('reward_detail', [
            'quest_id' => $quest->id,
            'quest_type_id' => $quest->quest_type_id,
            'member_id' => $this->member->id,
            'reward' => 50,
            'valid_amount' => $totalAmount,
            'status' => RewardDetail::STATUS_PROCESSING,
            'bet_id' => json_encode([1, 2]),
            'applied_at' => $appliedAt,
        ]);

        // 第三次投注，達到第二階段流水量
        $betId = 3;
        $validAmount = 500;
        $gameTime = Carbon::now();
        $this->callCountRewards($betId, null, $validAmount, $gameTime);
        $totalAmount = $totalAmount + $validAmount;
        $computeAmount = (($totalAmount * 0.1) > 70) ? 70 : ($totalAmount * 0.1);

        $this->assertDatabaseHas('reward_detail', [
            'quest_id' => $quest->id,
            'quest_type_id' => $quest->quest_type_id,
            'member_id' => $this->member->id,
            'reward' => $computeAmount,
            'valid_amount' => $totalAmount,
            'status' => RewardDetail::STATUS_PROCESSING,
            'bet_id' => json_encode([1, 2, 3]),
            'applied_at' => $appliedAt,
        ]);

        // 第四次投注，達到第三階段流水量
        $betId = 4;
        $validAmount = 2000;
        $gameTime = Carbon::now();
        $this->callCountRewards($betId, null, $validAmount, $gameTime);
        $totalAmount = $totalAmount + $validAmount;

        $this->assertDatabaseHas('reward_detail', [
            'quest_id' => $quest->id,
            'quest_type_id' => $quest->quest_type_id,
            'member_id' => $this->member->id,
            'reward' => 150,
            'valid_amount' => $totalAmount,
            'status' => RewardDetail::STATUS_COMPLETED,
            'bet_id' => json_encode([1, 2, 3, 4]),
            'applied_at' => $appliedAt,
        ]);
    }

    /**
     *
     */
    private function callCountRewards($betId, $gameCode, $validAmount, $gameTime)
    {
        if (is_null($gameCode)) {
            $gameCode = rand(999, 5000);
        }

        if (is_null($gameTime)) {
            $gameTime = Carbon::now();
        }

        $this->questService->countRewards($betId, $gameCode, $validAmount, $gameTime);
    }

    /**
     * create a quest
     *
     * @param array $qusetData
     * @return \App\Models\QuestReward
     */
    private function createQuest(array $qusetData = [])
    {
        $group = factory(QuestGroup::class)->create([
            'franchisee_id' => $this->franchisee->id,
        ]);
        $default = [
            'franchisee_id' => $this->franchisee->id,
            'group_id' => $group->id,
            'club_rank_id' => $this->clubRank->id,
            'quest_name' => 'unit#testing',
        ];

        $qusetData = array_merge($default, $qusetData);
        $quest = factory(QuestReward::class)->create($qusetData);

        return $quest;
    }
}
