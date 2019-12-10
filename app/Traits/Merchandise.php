<?php

namespace App\Traits;

trait Merchandise
{
	public function purchases()
	{
		return $this->morphMany('App\Merchandise\Purchase', 'item');
	}
}
