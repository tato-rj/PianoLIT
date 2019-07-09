<?php

namespace App\Resources\ChordFinder;

class Validator
{
	public function __construct(ChordFinder $finder)
	{
		$this->finder = $finder;
	}

	public function run()
	{
		if (! $this->finder->notes || count($this->finder->notes) < 3)
			abort(422, 'We need a minimum of 3 notes');
	}

	public function third()
	{
		if ($this->finder->chord[1]['interval']['distance'] == 3 || $this->finder->chord[2]['interval']['distance'] == 3) {
			return $this->finder->chord[1]['interval']['type'];
		} else {
			abort(422, 'This chord is missing a third');			
		}
	}

	public function fifth()
	{
		if ($this->finder->chord[2]['interval']['distance'] == 5 || $this->finder->chord[3]['interval']['distance'] == 5)
			return $this->finder->chord[2]['interval']['type'];

		return 'perfect';
	}
}
