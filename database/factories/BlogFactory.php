<?php

use App\Blog\{Post, Topic};
use App\Admin;
use Faker\Generator as Faker;

$factory->define(Post::class, function (Faker $faker) {
    return [
        'slug' => str_slug($faker->sentence(8)),
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
        'title' => $faker->sentence(8),
        'description' => $faker->paragraph(2),
        'content' => $faker->paragraph,
        'cover_path' => $faker->image(),
        'cover_credits' => $faker->sentence,
        'views' => $faker->numberBetween(0,500),
        'reading_time' => $faker->numberBetween(0,10),
        'published_at' => null
    ];
});

$factory->define(Topic::class, function (Faker $faker) {
    return [
        'type' => $faker->word,
        'slug' => str_slug($faker->word),
        'name' => $faker->word,
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
    ];
});