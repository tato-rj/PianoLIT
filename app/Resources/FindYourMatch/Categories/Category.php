<?php

namespace App\Resources\FindYourMatch\Categories;

use Illuminate\Database\Eloquent\Builder;
use App\Resources\FindYourMatch\Builder\{Required, Suggested};

abstract class Category
{
	protected $query, $name;
		
	public function __construct()
	{
		$this->name = $this->getName();
		$this->query = collect();
	}

	public function require(Builder $builder)
	{
		return (new Required($builder))->table($this->relationship)->column($this->column)->build($this->query);
	}


	public function suggest(Builder $builder)
	{
		return (new Suggested($builder))->table($this->relationship)->column($this->column)->build($this->query);
	}

	public function build(Builder $builder)
	{
		if ($this->isRequired())
			return $this->require($builder);
		
		return $this->suggest($builder);
	}

	public function get()
	{
		$this->prioritizeDuplicates();

		$this->prioritizeRelevance();

		return collect([$this->name => $this->query]);
	}

	public function take($query)
	{
		$this->query->push($query);
	}

	public function prioritizeDuplicates()
	{
		$duplicates = $this->query->duplicates()->values();

		if ($duplicates->isNotEmpty())
			$this->query = $duplicates->random();
	}

	public function prioritizeRelevance()
	{
		if (! property_exists($this, 'relevance') || is_string($this->query))
			return null;

		$indexes = collect();

		foreach ($this->query as $keyword) {
			if ($key = array_search($keyword, $this->relevance))
				$indexes->push($key);
		}

		if (array_key_exists($indexes->min(), $this->relevance))
			$this->query = $this->relevance[$indexes->min()];
	}

	public function isRequired()
	{
		return is_string($this->query);
	}

	public function getName() {
		$path = explode('\\', get_class($this));

		return strtolower(array_pop($path));
	}
}