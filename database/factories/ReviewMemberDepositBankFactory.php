<?php

use App\Models\Review\ReviewMemberDepositBank;
use Faker\Generator as Faker;

$factory->define(ReviewMemberDepositBank::class, function (Faker $faker) {
    return [
        'apply_amount' => 0,
        'real_amount' => 0,
        'fee' => 0,
        'bank_id' => rand(999, 9999),
        'payer_name' => 'unit tester',
        'payee_name' => 'unit tester',
        'payee_account' => '123456789',
        'payee_bank_name' => 'unit bank',
        'review_step_count' => 1,
        'review_step' => 1,
        'review_role_id' => rand(999, 9999),
        'last_reviewer_id' => rand(999, 9999),
    ];
});
