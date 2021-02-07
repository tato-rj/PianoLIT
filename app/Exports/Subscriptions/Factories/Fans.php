<?php

namespace App\Exports\Subscriptions\Factories;

use App\User;

class Fans extends ExportFactory
{
	public function generate()
	{
		$this->getQuery(User::query());

		return $this->handle();
	}

	public function filter()
	{
		return $this->query->sortByDesc(function($value, $key) {
			return $value->logs_count;
		})->take(200);
	}
}