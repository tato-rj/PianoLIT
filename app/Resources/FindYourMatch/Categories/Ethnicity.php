<?php

namespace App\Resources\FindYourMatch\Categories;

use Illuminate\Database\Eloquent\Builder;
use App\Resources\FindYourMatch\Builder\{Required, Suggested};

class Ethnicity extends Category
{	
	protected $relationship = 'composer';
	protected $column = 'ethnicity';
}
