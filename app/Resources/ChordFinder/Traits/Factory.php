<?php

namespace App\Resources\ChordFinder\Traits;

trait Factory
{
	protected $whiteEnharmonics = [
		'e+' => 'f',
		'f-' => 'e',
		'b+' => 'c',
		'c-' => 'b'
	];

	protected $steps = ['+', 'a', '+', 'b', 'c', '+', 'd', '+', 'e', 'f', '+', 'g', '+', 'a', '+', 'b', 'c', '+', 'd', '+', 'e', 'f', '+', 'g', '+'];

	protected $letters = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'a', 'b', 'c', 'd', 'e', 'f', 'g'];

	protected $intervals = [
		2 => [
			1 => 'minor',
			2 => 'major',
		],
		3 => [
			3 => 'minor',
			4 => 'major',
		],
		4 => [
			4 => 'diminished',
			5 => 'perfect',
			6 => 'augmented',
		],
		5 => [
			6 => 'diminished',
			7 => 'perfect',
			8 => 'augmented'
		],
		6 => [
			8 => 'minor',
			9 => 'major',
		],
		7 => [
			9 => 'diminished',
			10 => 'minor',
			11 => 'major',
		],
	];

	protected $nonValidChordIntervals = [4 => [4,6]];

	public function intervals($absolute)
	{
		if (! $absolute) {
			foreach ($this->nonValidChordIntervals as $step => $array) {
				foreach ($array as $interval) {
					unset($this->intervals[$step][$interval]);
				}
			}
		}

		return $this->intervals;
	}
}