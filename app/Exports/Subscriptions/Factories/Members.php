<?php

namespace App\Exports\Subscriptions\Factories;

use App\User;

class Members implements ExportFactory
{
	protected $data;
	protected $exceptions = ['mariateresasaldanha@globo.com', 'arygnogueira@gmail.com', 'caterina.dare@frontiersin.org', 'elena.dare@bisley.com'];

	public function generate()
	{
		return $this->data = User::has('membership')->get()->except($this->exceptions)->pluck('email')->toArray();
	}
}