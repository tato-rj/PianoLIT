<?php

namespace App\Filters;

use App\Filters\Traits\Topics;

class QuizFilters extends Filters
{
	use Topics;
	
	protected $filters = ['topics', 'level'];

	public function level($level)
	{
		return $this->builder->whereHas('level', function ($query) use ($level) {
			$query->where('slug', $level);
        });		
	}
}
