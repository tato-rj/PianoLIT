<?php

namespace App\Exports\Subscriptions\Factories;

use App\User;

class Fans implements ExportFactory
{
	public function generate()
	{
		$this->getQuery(User::has('membership'));

		return $this->handle();
	}
}