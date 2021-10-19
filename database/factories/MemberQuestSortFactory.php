<?php

use App\Models\MemberQuestSort;
use Faker\Generator as Faker;

$factory->define(MemberQuestSort::class, function (Faker $faker) {
    return [
            'member_id' => 0,
            'sort' => json_encode([]),
        ];
});