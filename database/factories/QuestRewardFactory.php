<?php

use App\Models\QuestReward;
use Faker\Generator as Faker;

$factory->define(QuestReward::class, function (Faker $faker) {
    return [
        'quest_name' => 'quest#testing',
        'enabled' => true,
        'order' => 1,
        'information_display' => false,
    ];
});
