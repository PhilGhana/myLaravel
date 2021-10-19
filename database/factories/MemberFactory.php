<?php

use App\Models\ClubRank;
use App\Models\Franchisee;
use App\Models\Member;
use Faker\Generator as Faker;

$factory->define(Member::class, function (Faker $faker) {
        return [
                'franchisee_id' => 99,
                'account' => $faker->firstName,
                'password' => $faker->password,
                'name' => 'unittest',
                'club_id' => 99,
                'club_rank_id' => 99,
                'enabled' => true,
                'locked' => false,
                'alv1' => 99,
                'alv2' => 99,
                'alv3' => 99,
                'alv4' => 99,
                'alv5' => 99,
                'mlv3' => 99,
                'mlv2' => 99,
                'mlv1' => 99,
                'invitation_code' => $faker->password,
            ];
    });

$factory->state(Member::class, 'fId', [
        'franchisee_id' => 'fId',
    ]);
