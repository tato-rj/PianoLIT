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

	public function defined($root)
	{
		$inversions = $this->all();

		foreach ($inversions as $index => $inversion) {
			if ($inversion['chord'][0][0] != strtolower($root[0]))
				unset($inversions[$index]);
		}

		return array_values($inversions);
	}
}
