<?php

namespace App\Exports\Subscriptions\Factories;

use App\User;

class Fans extends ExportFactory
{
	public function __construct()
	{
		$this->setQuery(User::query());
	}

	public function filter()
	{
		return $this->query->sortByDesc(function($value, $key) {
			return $value->logs_count;
		})->take(200);
	}
}