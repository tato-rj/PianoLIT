<?php

use App\Payments\Membership;
use App\Payments\Sources\Apple;
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
