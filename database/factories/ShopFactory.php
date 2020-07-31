<?php

use App\Shop\{eBook, eBookTopic, eScore, eScoreTopic};
use App\Admin;
use Faker\Generator as Faker;

$factory->define(eBook::class, function (Faker $faker) {
    return [
        'slug' => str_slug($faker->sentence),
        'title' => $faker->sentence,
        'subtitle' => $faker->sentence,
        'description' => $faker->sentence,
        'cover_path' => $faker->url,
        'shelf_cover_path' => $faker->url,
        'previews' => '',
        'score' => 0,
        'pages_count' => 10,
        'pdf_path' => $faker->url,
        'epub_path' => $faker->url,
        'price' => 500,
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

$factory->define(eScore::class, function (Faker $faker) {
    return [
        'slug' => str_slug($faker->sentence),
        'title' => $faker->sentence,
        'subtitle' => $faker->sentence,
        'description' => $faker->sentence,
        'cover_path' => $faker->url,
        'shelf_cover_path' => $faker->url,
        'previews' => '',
        'score' => 0,
        'pages_count' => 10,
        'pdf_path' => $faker->url,
        'price' => 500,
        'discount' => 25,
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
    ];
});

$factory->define(eScoreTopic::class, function (Faker $faker) {
    return [
        'slug' => str_slug($faker->word),
        'name' => $faker->word,
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
    ];
});
