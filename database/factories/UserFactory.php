<?php

use App\{User, Admin, Membership, Subscription};
use Illuminate\Support\Str;
use Faker\Generator as Faker;

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
        'origin' => 'test'
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

$factory->define(Subscription::class, function (Faker $faker) {
    return [
        'email' => $faker->unique()->safeEmail,
        'origin_url' => $faker->url,
        'newsletter_list' => true,
        'birthday_list' => true
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