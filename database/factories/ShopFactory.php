<?php

use App\Shop\{eBook, eBookTopic};
use App\Admin;
use Faker\Generator as Faker;

$factory->define(eBook::class, function (Faker $faker) {
    return [
        'slug' => str_slug($faker->sentence),
        'title' => $faker->sentence,
        'subtitle' => $faker->sentence,
        'description' => $faker->sentence,
        'mockup_path' => $faker->url,
        'previews' => '',
        'score' => 0,
        'pages_count' => 10,
        'pdf_path' => $faker->url,
        'epub_path' => $faker->url,
        'price' => 50,
        'discount' => 25,
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
    ];
});

$factory->define(eBookTopic::class, function (Faker $faker) {
    return [
        'slug' => str_slug($faker->word),
        'name' => $faker->word,
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
    ];
});