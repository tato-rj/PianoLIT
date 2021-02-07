<?php

namespace App\Exports\Subscriptions\Factories;

use App\User;

class Members extends ExportFactory
{
	public function generate()
	{
		return User::has('membership')
					 ->whereNotIn('email', $this->exceptions)
					 ->get()
					 ->pluck('email')
					 ->toArray();
	}
}