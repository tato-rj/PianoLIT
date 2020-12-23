<?php

namespace App\Resources\FindYourMatch;

use App\Resources\FindYourMatch\Categories\Category;
use App\Tag;

abstract class QuizFactory
{
	public function __construct()
	{
		foreach ($this->categories as $category => $class) {
			$this->$category = new $class;
		}
	}

	public function getResults()
	{
		$results = collect(['and' => collect(), 'or' => collect()]);

		foreach ($this->categories as $category => $class) {
			$keywords = $this->$category->get();
			$results['and']->push($keywords['and']);
			$results['or']->push($keywords['or']);
		}
		
		$results['and'] = $results['and']->flatten()->notNull();
		$results['or'] = $results['or']->flatten()->notNull();

		return $results;
	}

	public function isValid($category)
	{
		return property_exists($this, $category) && $this->$category instanceof Category;
	}
}