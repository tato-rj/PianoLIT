<?php

namespace App\Resources\ChordFinderOLD;

class Validator
{
	public function __construct(ChordFinder $finder)
	{
		$this->finder = $finder;
	}

	public function run()
	{
		if (! $this->finder->notes || count($this->finder->notes) < 2)
			abort(422, 'We need a minimum of 2 notes');

		$this->finder->notes = array_map('strtolower', $this->finder->notes);

		foreach ($this->finder->notes as $note) {
			if (! in_array($note[0], ['a', 'b', 'c', 'd', 'e', 'f', 'g']))
				abort(422, 'The note ' . $note . ' is not valid');
		}
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
