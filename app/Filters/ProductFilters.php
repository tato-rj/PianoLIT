<?php

namespace App\Filters;

class ProductFilters extends Filters
{
	protected $filters = ['topics'];

	public function topics($topics)
	{
		return $this->builder->whereHas('topics', function ($query) use ($topics) {
			$query->whereIn('slug', $topics);
        });		
	}
}
