<?php

namespace App\Resources\FindYourMatch\Builder;

use Illuminate\Database\Eloquent\Builder;

class Required
{
	protected $builder, $table, $column;

	public function __construct(Builder $builder)
	{
		$this->builder = $builder;
	}

	public function table($table)
	{
		$this->table = $table;

		return $this;
	}
	
	public function column($column)
	{
		$this->column = $column;

		return $this;
	}

	public function build($query)
	{
		return	$this->builder->whereHas($this->table, function($q) use ($query) {
					$q->where($this->column, 'like', '%'.$query.'%');
				});
	}
}
