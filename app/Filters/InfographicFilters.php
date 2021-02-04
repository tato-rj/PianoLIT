<?php

namespace App\Filters;

use App\Filters\Traits\Topics;

class InfographicFilters extends Filters
{
	use Topics;
	
	protected $filters = ['topics'];
}
