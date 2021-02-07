<?php

namespace App\Exports\Subscriptions\Factories;

use App\User;

class Members implements ExportFactory
{
	public function generate()
	{
		return $this->data = User::has('membership')->get()->pluck('email')->toArray();
	}
}