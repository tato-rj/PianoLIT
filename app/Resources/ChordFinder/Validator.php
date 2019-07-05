<?php

namespace App\Resources\ChordFinder;

class Validator
{
	public function run(ChordFinder $finder)
	{
		if (count($finder->notes) < 3)
			abort(422);
	}
}
