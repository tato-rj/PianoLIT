<?php

namespace App\Stats;

abstract class StatsFactory
{
	protected $color = [
	    'purple' => '#9561e2', 
	    'red' => '#e3342f', 
	    'orange' => '#f6993f', 
	    'green' => '#38c172', 
	    'cyan' => '#4dc0b5', 
	    'blue' => '#3490dc', 
	    'pink' => '#f66d9b',
	    'grey' => '#cecccc'
	];

	abstract public function get();
}
