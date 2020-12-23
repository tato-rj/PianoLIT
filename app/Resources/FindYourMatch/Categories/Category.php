<?php

namespace App\Resources\FindYourMatch\Categories;

abstract class Category
{
	protected $query, $and, $or;

	public function __construct()
	{
		$this->query = collect();
	}

	public function get()
	{
		$this->setPriority();
		
		return collect(['and' => $this->and, 'or' => $this->or]);
	}

	public function take($query)
	{
		$this->query->push($query);
	}

	public function setPriority()
	{
		$duplicates = $this->query->duplicates()->values();

		$this->and = $duplicates;
		$this->or = $this->query->diff($duplicates);
	}
}