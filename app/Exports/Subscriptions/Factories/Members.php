<?php

namespace App\Exports\Subscriptions\Factories;

use App\User;

class Members extends ExportFactory
{
	public function __construct()
	{
		$this->setQuery(User::has('membership'));
	}
}