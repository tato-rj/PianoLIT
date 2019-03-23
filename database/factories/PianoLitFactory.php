<?php

use App\{Composer, Country, Piece, Tag, Playlist, Admin};
use Illuminate\Support\Str;
use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(Playlist::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
    ];
});

$factory->define(Composer::class, function(Faker $faker) {
    $periods = periods();

    return [
            'name' => $faker->name,
            'biography' => $faker->paragraph,
            'curiosity' => $faker->paragraph,
            'period' => $periods[rand(0, count($periods) - 1)],
            'country_id' => function() {
                return create(Country::class)->id;
            },
            'date_of_birth' => $faker->date(),
            'date_of_death' => $faker->date(),
            'creator_id' => function() {
                return create(Admin::class)->id;
            },
    ];
});

$factory->define(Piece::class, function (Faker $faker) {
    return [
            'name' => $faker->name,
            'nickname' => $faker->name,
            'catalogue_name' => randval(catalogues()),
            'catalogue_number' => rand(1,100),
            'collection_name' => $faker->name,
            'collection_number' => rand(1,10),
            'movement_number' => rand(1,4),
            'key' => randval(keys()),
            'curiosity' => '',
            'audio_path' => '',
            'audio_path_rh' => '',
            'audio_path_lh' => '',
            'itunes' => '',
            'youtube' => '',
            'score_path' => '',
            'score_editor' => '',
            'score_publisher' => '',
            'score_copyright' => '',
            'composer_id' => function() {
                return create(Composer::class)->id;
            },
            'creator_id' => function() {
                return create(Admin::class)->id;
            }
    ];
});

$factory->define(Country::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'nationality' => $faker->word
    ];
});

$factory->define(Tag::class, function (Faker $faker) {
    return [
        'type' => $faker->word,
        'name' => $faker->word,
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
    ];
});
