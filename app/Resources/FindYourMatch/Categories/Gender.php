<?php

namespace App\Resources\FindYourMatch\Categories;

use Illuminate\Database\Eloquent\Builder;
use App\Resources\FindYourMatch\Builder\{Required, Suggested};

class Gender extends Category
{
	protected $relationship = 'composer';
	protected $column = 'gender';
}
