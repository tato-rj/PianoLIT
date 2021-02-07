<?php

namespace App\Exports\Subscriptions\Factories;

abstract class ExportFactory
{
	protected $exceptions = ['mariateresasaldanha@globo.com', 'arygnogueira@gmail.com', 'caterina.dare@frontiersin.org', 'elena.dare@bisley.com'];

	public function generate();
}
