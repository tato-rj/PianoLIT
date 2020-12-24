<?php

namespace App\Resources\FindYourMatch\Categories;

use Illuminate\Database\Eloquent\Builder;
use App\Resources\FindYourMatch\Builder\{Required, Suggested};

class Period extends Category
{
	protected $relationship = 'tags';
	protected $column = 'name';
}
