<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\{Performance, User, Piece, Clap};
use Faker\Generator as Faker;

$factory->define(Performance::class, function (Faker $faker) {
    return [
        'user_id' => function() {
            return create(User::class)->id;
        },
        'piece_id' => function() {
            return create(Piece::class)->id;
        }
    ];
});

$factory->define(Clap::class, function (Faker $faker) {
    return [
        'user_id' => function() {
            return create(User::class)->id;
        },
        'performance_id' => function() {
            return create(Performance::class)->id;
        },
    ];
});
