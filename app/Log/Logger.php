<?php

namespace App\Log;

use App\User;

abstract class Logger
{
	abstract public function getKey();
	abstract public function getData();

	public function push()
	{
		return (new LogFactory)->push($this);
	}
}
