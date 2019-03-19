<?php

use App\{User, Composer, Country, Admin, Piece, Tag, Membership, Playlist};
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

$factory->define(User::class, function (Faker $faker) {
    $age = ['under 13', '13 to 18', '18 to 25', '25 to 35', '35 to 45', '45 and up'];
    $experience = ['none', 'little', 'a lot'];
    $occupation = ['student', 'teacher', 'music lover'];
    // $favorite = \App\Projects\PianoLit\Piece::inRandomOrder()->first();
    $locale = ['en_US', 'en_GB', 'it_CH', 'it_IT', 'fr_BE', 'fr_CA', 'pt_BR'];

    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $faker->unique()->safeEmail,
        'password' => \Hash::make('secret'),
        'locale' => randval($locale),
        'age_range' => randval($age),
        'experience' => randval($experience),
        'preferred_piece_id' => 1,//$favorite->id,
        'occupation' => randval($occupation),
        'trial_ends_at' => now()->addWeek()
    ];
});

$factory->define(Admin::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'role' => 'manager',
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        'remember_token' => Str::random(10),
    ];
});

$factory->define(Playlist::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
    ];
});

$factory->define(Membership::class, function (Faker $faker) {
    return [
        'user_id' => function() {
            return create(User::class)->id;
        },
        'plan' => $faker->word,
        'latest_receipt' => '',
        'password' => \Hash::make('secret'),
        'latest_receipt_info' => '',
        'renews_at' => now()->copy()->addMonth()
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
