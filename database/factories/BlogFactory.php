<?php

use App\Blog\Post;
use App\Admin;
use Faker\Generator as Faker;

$factory->define(Post::class, function (Faker $faker) {
    return [
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
        'title' => $faker->sentence,
        'content' => $faker->paragraph,
        'cover_path' => $faker->image(),
        'is_published' => $faker->boolean(),
        'views' => $faker->numberBetween(0,500)
    ];
});
