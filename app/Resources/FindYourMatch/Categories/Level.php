<?php

namespace App\Resources\FindYourMatch\Categories;

use Illuminate\Database\Eloquent\Builder;
use App\Resources\FindYourMatch\Builder\{Required, Suggested};

class Level extends Category
{
	protected $relevance = ['advanced', 'late intermediate', 'early intermediate', 'late beginner', 'early beginner', 'elementary'];
	protected $relationship = 'tags';
	protected $column = 'name';
}
