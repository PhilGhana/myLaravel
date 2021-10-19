<?php

use Faker\Generator as Faker;
use App\Models\Report\PlatformReportAg;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Report\PlatformReportAgDetail;

// $platformReportAg = DB::table('platform_report_ag')->orderByRaw("CAST(id AS INT) DESC")->first();
// $id = $platformReportAg->id + 1;
$id = 1;
$date = Carbon::now();
$betAt = $date->subWeeks(rand(4, 52))->format('Y-m-d H:i:s'); // 下注時間(以當前日期為基準，產生隨機時間)
$lotteryAt = Carbon::parse($betAt)->addWeeks(rand(1, 4))->format('Y-m-d H:i:s');  // 開獎時間
$factory->define(PlatformReportAgDetail::class, function (Faker $faker) use ($id, $betAt, $lotteryAt) {
    return [
        'id' => $id,
        'bet_at' => $betAt,
        'lottery_at' => $lotteryAt,
        'round' => null,
        'content' => null,
        'odds' => $faker->randomFloat(2, 0, 99),
        'lottery_result' => null,
        'report_id' => null,
        'source_valid_amount' => null,
        'ip' => '127.0.0.1'
    ];
});
