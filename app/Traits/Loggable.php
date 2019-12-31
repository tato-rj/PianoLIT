<?php

namespace App\Traits;

use App\Log\LogFactory;

trait Loggable
{
	public function log($type = null)
	{
		return (new LogFactory)->get($this->id, $type);
	}

	public function lastActive($type = null)
	{
		return (new LogFactory)->last($this->id, $type);		
	}
}
