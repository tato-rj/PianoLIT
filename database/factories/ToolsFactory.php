<?php

use App\Quiz;
use Faker\Generator as Faker;

$factory->define(Quiz::class, function (Faker $faker) {
	$questions = [
		['Q' => 'Here is a question?', 'A' => ['Answer 1', 'Answer 2[x]']]
	];

    return [
    	'slug' => str_slug($faker->word),
    	'title' => $faker->word,
    	'description' => $faker->sentence,
        'questions' => serialize($questions)
    ];
});
