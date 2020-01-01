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

	public function cleanRequest($except = [])
	{
		$request = request()->except($except);
		foreach ($request as $key => $value) {
			if (! $value)
				unset($request[$key]);
		}

		return $request;
	}
}
