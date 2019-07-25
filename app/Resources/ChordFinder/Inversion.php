<?php

namespace App\Resources\ChordFinder;

class Inversion
{
	protected $chord;

	public function __construct(array $chord)
	{
		$this->chord = $chord;	
	}

	public function get()
	{
		$root = array_shift($this->chord);
		array_push($this->chord, $root);

		return array_values($this->chord);
	}

	public function all()
	{
		$inversions = [];
		foreach ($this->chord as $index => $notes) {
			$inversions[$index]['chord'] = $this->get();
		}
		$first = array_pop($inversions);
		array_unshift($inversions, $first);
		return array_values($inversions);
	}
}
