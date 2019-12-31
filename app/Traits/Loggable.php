<?php

namespace App\Traits;

use App\Log\LogFactory;

trait Loggable
{
	public function log($type = null)
	{
		return (new LogFactory)->get($this->id, $type);
	}
}
