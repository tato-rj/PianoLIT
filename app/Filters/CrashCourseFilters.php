<?php

namespace App\Filters;

use App\Filters\Traits\Topics;

class CrashCourseFilters extends Filters
{
	use Topics;

	protected $filters = ['topics'];
}
