<?php

use App\Models\QuestGroup;
use Faker\Generator as Faker;

$factory->define(QuestGroup::class, function (Faker $faker) {
    return [
        'name' => 'group#testing',
        'order' => 1,
    ];
});
