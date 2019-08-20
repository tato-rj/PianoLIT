<?php

use App\Quiz\{Quiz, QuizResult};
use App\Admin;
use Faker\Generator as Faker;

$factory->define(Quiz::class, function (Faker $faker) {
    $questions = [];

    for ($i=0; $i<10; $i++) {
        array_push($questions, ['Q' => $faker->sentence, 'A' => [$faker->sentence, $faker->sentence . '[x]']]);
    }

	$feedback = ['Bad', 'Average', 'Good', 'Excellent', 'Perfect'];

    return [
    	'slug' => str_slug($faker->sentence),
        'creator_id' => function() {
            return create(Admin::class)->id;
        },
    	'title' => $faker->sentence,
    	'description' => $faker->sentence,
    	'cover_path' => $faker->url,
        'questions' => serialize($questions),
        'feedback' => serialize($feedback)
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