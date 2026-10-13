<?php

use App\Payments\Membership;
use App\{User, Admin, Subscription, TutorialRequest, Piece, Location, Rating};
use App\CrashCourse\{CrashCourse, CrashCourseLesson, CrashCourseSubscription, CrashCourseTopic};
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

$factory->define(TutorialRequest::class, function (Faker $faker) {
    return [
        'user_id' => function() {
            return create(User::class)->id;
        },
        'piece_id' => function() {
            return create(Piece::class)->id;
        },
    ];
});

$factory->define(CrashCourse::class, function (Faker $faker) {
    return [
        'slug' => str_slug($faker->sentence(8)),
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
        'title' => $faker->sentence,
        'description' => $faker->sentence,
    ];
});

$factory->define(CrashCourseLesson::class, function (Faker $faker) {
    return [
        'crash_course_id' => function() {
            return create(CrashCourse::class)->id;
        },
        'subject' => $faker->sentence,
        'body' => $faker->sentence,
        'reading_time' => 1,
        'order' => 0
    ];
});

$factory->define(CrashCourseSubscription::class, function (Faker $faker) {
    $crashcourse = create(CrashCourse::class);
    $subscription = create(Subscription::class);

    return [
        'first_name' => $faker->firstName,
        'email' => $subscription->email,
        'subscriber_id' => function() use ($subscription) {
            return $subscription->id;
        },
        'crash_course_id' => function() use ($crashcourse) {
            return $crashcourse->id;
        },
        'crash_course_title' => $crashcourse->title,
    ];
});

$factory->define(CrashCourseTopic::class, function (Faker $faker) {
    return [
        'slug' => str_slug($faker->word),
        'name' => $faker->word,
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
    ];
});

$factory->define(Location::class, function (Faker $faker) {
    return [
        'user_id' => function() {
            return create(User::class)->id;
        },
        'ip' => $faker->ipv4,
        'countryName' => $faker->country,
        'countryCode' => $faker->countryCode,
        'regionCode' => $faker->stateAbbr,
        'regionName' => $faker->state,
        'cityName' => $faker->city,
        'latitude' => $faker->latitude(),
        'longitude' => $faker->longitude(),
    ];
});

$factory->define(Rating::class, function (Faker $faker) {
    return [
        'user_id' => function() {
            return create(User::class)->id;
        },
        'score' => $faker->numberBetween(1,5),
    ];
});
