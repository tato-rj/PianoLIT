<?php

namespace App\Resources\Technique;

use App\Resources\Technique\Traits\{Factory, Keys};

abstract class Technique
{
	use Factory, Keys;

	protected $key, $name;

	public function __construct($key)
	{
		$this->validator()->check($key);

		$this->key = ucfirst($key);
		$this->name = $this->name();
	}

	public function generate()
	{
		$results = [$this->name => []];

		foreach ($this->types as $type) {
			array_push($results[$this->name], $this->{$type}());
		}

		return $results;
	}

	public function types($types)
	{
		foreach ($types as $type) {
			if (! method_exists($this, $type))
				abort(422, 'Sorry, the type '.$type.' doesn\'t exist');	
		}

		$this->types = $types;

		return $this;
	}

	public function name()
	{
		return strtolower((new \ReflectionClass($this))->getShortName());
	}

	public function validator()
	{
		return new Validator;
	}
}
