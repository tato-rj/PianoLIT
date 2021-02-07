<?php

namespace App\Exports\Subscriptions\Factories;

abstract class ExportFactory
{
	protected $query;
	protected $exceptions = ['mariateresasaldanha@globo.com', 'arygnogueira@gmail.com', 'caterina.dare@frontiersin.org', 'elena.dare@bisley.com'];

	protected function setQuery($query)
	{
		$this->query = $query->whereNotIn('email', $this->exceptions)->get();
	}

	public function filter()
	{
		return $this->query;
	}

	public function generate()
	{
		return $this->filter()->pluck('email')->toArray();
	}
}
