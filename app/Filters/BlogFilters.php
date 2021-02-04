<?php

namespace App\Filters;

use App\Filters\Traits\Topics;

class BlogFilters extends Filters
{
	use Topics;

	protected $filters = ['topics'];
}
