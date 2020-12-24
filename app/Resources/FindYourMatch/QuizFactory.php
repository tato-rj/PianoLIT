<?php

namespace App\Resources\FindYourMatch;

use App\Resources\FindYourMatch\Categories\Category;
use App\Resources\FindYourMatch\Traits\Categories;
use App\Tag;

abstract class QuizFactory
{
	use Categories;
	
	public function __construct()
	{
		$this->bootCategories();
	}

	public function getKeywords($flexible = false)
	{
		if ($flexible) {
			foreach ($this->categories as $category => $class) {
				if ($this->keywords->has($category)) {
					$this->keywords->forget($category);
					break;
				}
			}
		}

		return $this->keywords;
	}

	public function getResults()
	{
		$results = collect();

		foreach ($this->categories as $category => $class) {
			$results->push($this->$category->get());
		}

		return $results->flattenWithKeys();
	}
}