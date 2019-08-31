<?php

namespace App\Filters;

use Illuminate\Http\Request;

abstract class Filters
{
	protected $request, $builder;
	protected $filters = [];

	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	public function apply($builder)
	{
		$this->builder = $builder;

		foreach ($this->getFilters() as $filter => $value) {
			if (method_exists($this, $filter)) {
				if (is_array($this->request->$filter)) {
					$this->$filter($this->request->$filter);
				} else {
					$this->$filter(explode(' ', $this->request->$filter));
				}
			}
		}

		return $this->builder;		
	}

	public function getFilters()
	{
		return $this->request->only($this->filters);
	}
}
