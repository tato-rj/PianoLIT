<?php

use App\Billing\{Membership, Plan};
use App\Billing\Sources\{Apple, Stripe};
use App\User;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(Membership::class, function (Faker $faker) {
    $apple = create(Apple::class);

    return [
        'user_id' => function() {
            return create(User::class)->id;
        },
        'source_id' => $apple->id,
        'source_type' => get_class($apple)
    ];
});

$factory->define(Apple::class, function (Faker $faker) {
    return [
        'plan' => $faker->word,
        'latest_receipt' => '',
        'password' => \Hash::make('secret'),
        'latest_receipt_info' => '',
        'renews_at' => now()->copy()->addMonth()
    ];
});

$factory->define(Stripe::class, function (Faker $faker) {
    return [
        'plan' => $faker->word,
        'stripe_id' => $faker->md5,
        'card_brand' => $faker->word,
        'card_last_four' => '1234',
        'status' => 'active'
    ];
});

$factory->define(Plan::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'price' => 1000,
        'statement_descriptor' => $faker->sentence,
        'description' => $faker->paragraph,
        'interval' => $faker->word,
        'trial_period_days' => 7
    ];
});