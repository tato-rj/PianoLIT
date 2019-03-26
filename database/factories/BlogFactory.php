<?php

use App\Blog\Post;
use App\Admin;
use Faker\Generator as Faker;

$factory->define(Post::class, function (Faker $faker) {
    return [
        'slug' => str_slug($faker->sentence),
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
        'title' => $faker->sentence,
        'description' => $faker->paragraph,
        'content' => $faker->paragraph,
        'cover_path' => $faker->image(),
        'cover_credits' => $faker->sentence,
        'is_published' => false,
        'views' => $faker->numberBetween(0,500),
        'reading_time' => $faker->numberBetween(0,10)
    ];
});
