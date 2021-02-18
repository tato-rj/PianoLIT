<?php

use App\Shop\{eBook, eBookTopic, eScore, eScoreTopic};
use App\{Admin, Review, User};
use App\Merchandise\Purchase;
use Faker\Generator as Faker;

$factory->define(eBook::class, function (Faker $faker) {
    return [
        'slug' => str_slug($faker->sentence),
        'title' => $faker->sentence,
        'author' => $faker->word,
        'subtitle' => $faker->sentence,
        'description' => $faker->sentence,
        'cover_path' => $faker->url,
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
        'author' => $faker->word,
        'subtitle' => $faker->sentence,
        'description' => $faker->sentence,
        'cover_path' => $faker->url,
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

$factory->define(Review::class, function (Faker $faker) {
    $product = create(eBook::class);
    $user = create(User::class);

    return [
        'user_id' => $user->id,
        'reviewer' => $user->full_name,
        'reviewable_type' => get_class($product),
        'reviewable_id' => $product->id,
        'published_at' => now(),
        'rating' => $faker->numberBetween(1,5)
    ];
});

$factory->define(Purchase::class, function (Faker $faker) {
    $product = create(eBook::class);

    return [
        'user_id' => function() {
            return create(User::class)->id;
        },
        'item_id' => $product->id,
        'item_type' => get_class($product)
    ];
});
