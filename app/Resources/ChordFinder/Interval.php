<?php

namespace App\Resources\ChordFinder;

class Interval
{
	use Factory;

	public function find($first, $second, $octaveUp = false)
	{
		$firstPos = array_search($first, $this->tones());
		$secondPos = array_search($second, array_slice($this->tones(), $firstPos)) + $firstPos;
		$index = $secondPos - $firstPos;
		$type = $this->intervals($index)[0] == 'major' ? '' : $this->intervals($index)[0];
		$number = $octaveUp ? $this->intervals($index)[1] + 7 : $this->intervals($index)[1];

		return [
			'name' => $type . $number,
			'type' => $type,
			'number' => $number
		];
	}
}
