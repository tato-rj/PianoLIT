<?php

namespace App\Resources\FindYourMatch\Traits;

use App\Resources\FindYourMatch\Categories\{Composer, Gender, Ethnicity, Level, Mood, Period, Category};

trait Categories
{
	protected $categories = [
		'composer' => Composer::class,
		'period' => Period::class,
		'mood' => Mood::class,
		'gender' => Gender::class,
		'ethnicity' => Ethnicity::class,
		'level' => Level::class,
	];

	public function bootCategories()
	{
		foreach ($this->categories as $category => $class) {
			$this->$category = new $class;
		}
	}

	public function isValid($category)
	{
		return property_exists($this, $category) && $this->$category instanceof Category;
	}
}