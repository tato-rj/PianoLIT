<?php

namespace App\Exports\Subscriptions\Factories;

use App\User;

class Members extends ExportFactory
{
	public function generate()
	{
		$this->getQuery(User::has('membership'));

		return $this->handle();
	}
}