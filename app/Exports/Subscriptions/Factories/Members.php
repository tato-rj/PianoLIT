<?php

namespace App\Exports\Subscriptions\Factories;

use App\User;

class Members implements ExportFactory
{
	protected $data;

	public function generate()
	{
		return $this->data = User::has('membership')->get()->pluck('emails')->toArray();
	}
}