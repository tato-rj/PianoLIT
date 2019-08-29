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
		return $this->{$this->type}();
	}

	public function type($type)
	{
		if (! method_exists($this, $type))
			abort(422, 'Sorry, the type '.$type.' doesn\'t exist');	

		$this->type = $type;

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
