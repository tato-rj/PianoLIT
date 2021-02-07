<?php

namespace App\Exports\Subscriptions\Factories;

abstract class ExportFactory
{
	protected $query;
	protected $exceptions = ['mariateresasaldanha@globo.com', 'arygnogueira@gmail.com', 'caterina.dare@frontiersin.org', 'elena.dare@bisley.com'];

	abstract public function generate();

	public function alter()
	{
		return $this->query;
	}

	protected function getQuery($query)
	{
		$this->query = $query->whereNotIn('email', $this->exceptions)->get();
	}

	protected function handle()
	{
		return $this->alter()->pluck('email')->toArray();
	}
}
