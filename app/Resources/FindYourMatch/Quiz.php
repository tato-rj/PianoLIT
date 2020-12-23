<?php

namespace App\Resources\FindYourMatch;

use App\Resources\FindYourMatch\Traits\Questions;
use App\Resources\FindYourMatch\Categories\{Composer, Genre, Level, Mood, Nationality, Period};

class Quiz extends QuizFactory
{
	use Questions;

	protected $categories = [
		'composer' => Composer::class,
		'genre' => Genre::class,
		'level' => Level::class,
		'mood' => Mood::class,
		'nationality' => Nationality::class,
		'period' => Period::class
	];

	public function generate()
	{
		return $this->questions;
	}

	public function findKeywords($input)
	{
		$results = collect();
		$inputArray = explode(',', $input);

		foreach($inputArray as $pair) {
			$term = explode(':', $pair);

			$category = $term[0];

			if ($this->isValid($category))
				$this->$category->take($term[1]);
		}

		return $this->getResults();
	}
}