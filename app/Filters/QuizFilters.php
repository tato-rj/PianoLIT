<?php

namespace App\Filters;

use App\Filters\Traits\Topics;

class QuizFilters extends Filters
{
	use Topics;
	
	protected $filters = ['topics', 'level'];

	public function level($level)
	{
		$level = array_values(array_filter((array) $level, function ($value) { return is_string($value) && trim($value) !== ''; }));
		if (!$level) return $this->builder;
		return $this->builder->whereHas('level', function ($query) use ($level) {
			$query->whereIn('slug', $level);
        });		
	}
}
