<?php

namespace App\Traits;

trait Sortable
{
	public function scopeSorted($query)
	{
		return $query->orderBy('order');
	}
}
