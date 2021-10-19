<?php

use App\Models\ClubRank;
use Faker\Generator as Faker;

$factory->define(ClubRank::class, function (Faker $faker) {
    return [
        'franchisee_id' => 99,
        'name' => 'ClubRank#testing',
        'enabled' => true,
        'order' => 1,
        'deposit_per_max' => 0,
        'deposit_per_min' => 0,
        'deposit_day_times' => 0,
        'withdraw_per_max' => 0,
        'withdraw_per_min' => 0,
        'withdraw_day_times' => 0,
    ];
});
