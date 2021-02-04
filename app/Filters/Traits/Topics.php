<?php

namespace App\Filters\Traits;

trait Topics
{
	public function topics($topics)
	{
		return $this->builder->whereHas('topics', function ($query) use ($topics) {
			$query->whereIn('slug', $topics);
        });		
	}
}