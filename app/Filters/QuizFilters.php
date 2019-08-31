<?php

namespace App\Filters;

class QuizFilters extends Filters
{
	protected $filters = ['topics', 'level'];

	public function topics($topics)
	{
		return $this->builder->whereHas('topics', function ($query) use ($topics) {
			$query->whereIn('slug', $topics);
        });		
	}

	public function level($level)
	{
		return $this->builder->whereHas('level', function ($query) use ($level) {
			$query->where('slug', $level);
        });		
	}
}
