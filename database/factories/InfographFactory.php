<?php

use App\Infograph\{Infograph, Topic};
use App\Admin;
use Faker\Generator as Faker;

$factory->define(Infograph::class, function (Faker $faker) {
    return [
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
        'name' => $faker->sentence,
        'description' => $faker->sentence,
        'slug' => str_slug($faker->sentence),
        'cover_path' => $faker->url,
        'score' => 0
    ];
});

$factory->define(Topic::class, function (Faker $faker) {
    return [
        'slug' => str_slug($faker->word),
        'name' => $faker->word,
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
    ];
});