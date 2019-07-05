<?php

namespace App\Resources\ChordFinder;

trait Factory
{
	private $guide = 'abcdefgabcdefg';
	private $tones = 'a+bc+d+ef+g+a+bc+d+ef+g+';
	private $intervals = [
		2 => [
			0 => 'diminished',
			1 => 'minor',
			2 => 'major',
			3 => 'augmented',
		],
		3 => [
			2 => 'diminished',
			3 => 'minor',
			4 => 'major',
			5 => 'augmented',
		],
		4 => [
			4 => 'diminished',
			5 => 'perfect',
			6 => 'augmented'
		],
		5 => [
			6 => 'diminished',
			7 => 'perfect',
			8 => 'augmented'
		],
		6 => [
			7 => 'diminished',
			8 => 'minor',
			9 => 'major',
			10 => 'augmented',
		],
		7 => [
			9 => 'diminished',
			10 => 'minor',
			11 => 'major',
			12 => 'augmented',
		],
	];
	// private $intervals = [
	// 	0 => 'unisson',
	// 	1 => ['minor', 2],
	// 	2 => ['major', 2],
	// 	3 => ['minor', 3],
	// 	4 => ['major', 3],
	// 	5 => ['perfect', 4],
	// 	6 => ['diminished', 5],
	// 	7 => ['perfect', 5],
	// 	8 => ['minor', 6],
	// 	9 => ['major', 6],
	// 	10 => ['minor', 7],
	// 	11 => ['major', 7]
	// ];

	public function guide()
	{
		return $this->guide;
	}

	public function tones()
	{
		return str_split($this->tones);
	}

	public function getInterval($distance, $interval)
	{
		return [
			'number' => $distance,
			'type' => $this->intervals[$distance][$interval]
		];
	}
}
