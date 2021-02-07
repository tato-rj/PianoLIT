<?php

namespace App\Exports\Subscriptions\Factories;

use App\User;

class Members implements ExportFactory
{
	protected $exceptions = ['mariateresasaldanha@globo.com', 'arygnogueira@gmail.com', 'caterina.dare@frontiersin.org', 'elena.dare@bisley.com'];

	public function generate()
	{
		return User::has('membership')->whereNotIn('email', $this->exceptions)->get()->pluck('email')->toArray();
	}
}