<?php

use Faker\Generator as Faker;
use App\Models\Report\PlatformReportAg;
use App\Models\Member;
use App\Models\GamePlatform;
use App\Models\Game;
use App\Models\GameType;
use Illuminate\Support\Carbon;
use App\Models\Agent;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast;
use App\Models\Report\PlatformReportAgDetail;
use Illuminate\Http\Request;

$factory->define(PlatformReportAg::class, function (Faker $faker) {

    // $platformReportAg = DB::table('platform_report_ag')->orderByRaw("CAST(id AS INT) DESC")->first();  // id是字串，排序會有問題，所以強制轉型
    // $platformReportAg = DB::table('platform_report_ag')->orderBy('created_at', 'desc')->first();
    // $id = $platformReportAg->id + 1;
    $id = 1;
    $gameId = Game::all()->random()->id;
    $date = Carbon::now();
    $betAt = $date->subWeeks(rand(4, 52))->format('Y-m-d H:i:s'); // 下注時間(以當前日期為基準，產生隨機時間)
    $lotteryAt = Carbon::parse($betAt)->addWeeks(rand(1, 4))->format('Y-m-d H:i:s');  // 開獎時間

    return [
        // 'id' => factory(PlatformReportAg::class)->create()->id,  // <---- memory leak?
        // 'id' => PlatformReportAg::orderBy('id', 'desc')->first()->id + 1,
        'id' => $id,  // <---- 無法一次新建多筆 (primary key重複)
        'member_id' => Member::all()->random()->id,
        'platform_id' => 24,
        'game_id' => $gameId,
        'type' => Game::findOrError($gameId)->type,
        'status' => 'completed',
        'bet_at' => $betAt,
        'lottery_at' => $lotteryAt,
        'bet_amount' => $faker->randomFloat(4, 0, 100),
        'valid_amount' => $faker->randomFloat(4, 0, 100),
        'result_amount' => $faker->randomFloat(4, 0, 100),
        'water_percent' => $faker->randomFloat(2, 0, 99),
        'water_amount' => $faker->randomFloat(4, 0, 100),
        'subtotal' => $faker->randomFloat(4, 0, 100),
        'prize' => $faker->randomFloat(4, 0, 100),
        'tip' => $faker->randomFloat(4, 0, 100),
        'allocate_agent_water_percent' => $faker->randomFloat(2, 0, 99),
        'allocate_agent_water_amount' => $faker->randomFloat(4, 0, 100),
        'allocate_agent_bonus_percent' => $faker->randomFloat(0, 0, 99),
        'allocate_agent_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'allocate_member_bonus_percent' => $faker->randomFloat(0, 0, 99),
        'allocate_member_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'company_percent' => $faker->randomNumber(2),
        'company_amount' => $faker->randomFloat(4, 0, 100),
        'company_cost_agent_water_amount' => $faker->randomFloat(4, 0, 100),
        'company_cost_agent_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'company_cost_member_water_amount' => $faker->randomFloat(4, 0, 100),
        'company_cost_member_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'alv1' => Agent::where('level', 1)->get()->random()->id,
        'alv1_percent' => $faker->randomNumber(2),
        'alv1_amount' => $faker->randomFloat(4, 0, 100),
        'alv1_water_percent' => $faker->randomNumber(2),
        'alv1_water_amount' => $faker->randomFloat(4, 0, 100),
        'alv1_cost_agent_water_amount' => $faker->randomFloat(4, 0, 100),
        'alv1_cost_member_water_amount' => $faker->randomFloat(4, 0, 100),
        'alv1_cost_member_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'alv1_bonus_percent' => $faker->randomNumber(2),
        'alv1_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'alv2' => Agent::where('level', 2)->get()->random()->id,
        'alv2_percent' => $faker->randomNumber(2),
        'alv2_amount' => $faker->randomFloat(4, 0, 100),
        'alv2_water_percent' => $faker->randomNumber(2),
        'alv2_water_amount' => $faker->randomFloat(4, 0, 100),
        'alv2_cost_agent_water_amount' => $faker->randomFloat(4, 0, 100),
        'alv2_cost_member_water_amount' => $faker->randomFloat(4, 0, 100),
        'alv2_cost_member_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'alv2_bonus_percent' => $faker->randomNumber(2),
        'alv2_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'alv3' => Agent::where('level', 3)->get()->random()->id,
        'alv3_percent' => $faker->randomNumber(2),
        'alv3_amount' => $faker->randomFloat(4, 0, 100),
        'alv3_water_percent' => $faker->randomNumber(2),
        'alv3_water_amount' => $faker->randomFloat(4, 0, 100),
        'alv3_cost_agent_water_amount' => $faker->randomFloat(4, 0, 100),
        'alv3_cost_member_water_amount' => $faker->randomFloat(4, 0, 100),
        'alv3_cost_member_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'alv3_bonus_percent' => $faker->randomNumber(2),
        'alv3_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'alv4' => Agent::where('level', 4)->get()->random()->id,
        'alv4_percent' => $faker->randomNumber(2),
        'alv4_amount' => $faker->randomFloat(4, 0, 100),
        'alv4_water_percent' => $faker->randomNumber(2),
        'alv4_water_amount' => $faker->randomFloat(4, 0, 100),
        'alv4_cost_agent_water_amount' => $faker->randomFloat(4, 0, 100),
        'alv4_cost_member_water_amount' => $faker->randomFloat(4, 0, 100),
        'alv4_cost_member_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'alv4_bonus_percent' => $faker->randomNumber(2),
        'alv4_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'alv5' => Agent::where('level', 5)->get()->random()->id,
        'alv5_percent' => $faker->randomNumber(2),
        'alv5_amount' => $faker->randomFloat(4, 0, 100),
        'alv5_water_percent' => $faker->randomNumber(2),
        'alv5_water_amount' => $faker->randomFloat(4, 0, 100),
        'alv5_cost_agent_water_amount' => $faker->randomFloat(4, 0, 100),
        'alv5_cost_member_water_amount' => $faker->randomFloat(4, 0, 100),
        'alv5_cost_member_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'alv5_bonus_percent' => $faker->randomNumber(2),
        'alv5_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'mlv3' => Member::all()->random()->id,
        'mlv3_bonus_percent' => $faker->randomNumber(2),
        'mlv3_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'mlv2' => Member::all()->random()->id,
        'mlv2_bonus_percent' => $faker->randomNumber(2),
        'mlv2_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'mlv1' => Member::all()->random()->id,
        'mlv1_bonus_percent' => $faker->randomNumber(2),
        'mlv1_bonus_amount' => $faker->randomFloat(4, 0, 100),
        'cancel_at' => null,
    ];
});
