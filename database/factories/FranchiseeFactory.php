<?php

use App\Models\Franchisee;
use Faker\Generator as Faker;

$factory->define(Franchisee::class, function (Faker $faker) {
    return [
        'name' => 'franchisee#testing',
        'register_invitation_code' =>  $faker->password,
        'host' => $faker->ipv4,
        'enabled' => true,
        'locked' => false,
    ];
});
