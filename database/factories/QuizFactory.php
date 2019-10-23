<?php

use App\Quiz\{Quiz, QuizResult, Level, Topic};
use App\Admin;
use Faker\Generator as Faker;

$factory->define(Quiz::class, function (Faker $faker) {
    $questions = [];

    for ($i=0; $i<10; $i++) {
        array_push($questions, ['Q' => $faker->sentence, 'A' => [$faker->sentence, $faker->sentence . '[x]']]);
    }

    return [
    	'slug' => str_slug($faker->sentence),
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
        'level_id' => function() {
            return create(Level::class)->id;
        },
    	'title' => $faker->sentence,
    	'description' => $faker->sentence,
    	'cover_path' => $faker->url,
        'questions' => serialize($questions)
    ];
});

$factory->define(QuizResult::class, function (Faker $faker) {
    $quiz = create(Quiz::class);

    return [
        'quiz_id' => function() {
            return $quiz->id;
        },
        'score' => count($quiz->questions)
    ];
});

$factory->define(Level::class, function (Faker $faker) {
    return [
        'slug' => str_slug($faker->word),
        'name' => $faker->unique()->word
    ];
});

$factory->define(Topic::class, function (Faker $faker) {
    return [
        'slug' => str_slug($faker->word),
        'name' => $faker->word,
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
    ];
});