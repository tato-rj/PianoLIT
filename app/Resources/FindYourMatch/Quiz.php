<?php

namespace App\Resources\FindYourMatch;

use App\Resources\FindYourMatch\Traits\Questions;

class Quiz extends QuizFactory
{
	use Questions;

	protected $keywords;

	public function generate()
	{
		return $this->questions;
	}

	public function search()
	{
		return (new Search($this))->results();
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

		$this->keywords = $this->getResults();

		return $this;
	}
}