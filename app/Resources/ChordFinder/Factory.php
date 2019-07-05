<?php

namespace App\Resources\ChordFinder;

trait Factory
{
	private $guide = 'abcdefgabcdefg';
	private $tones = 'a+bc+d+ef+g+a+bc+d+ef+g+';
	private $intervals = [
		0 => 'unisson',
		1 => ['minor', 2],
		2 => ['major', 2],
		3 => ['minor', 3],
		4 => ['major', 3],
		5 => ['perfect', 4],
		6 => ['diminished', 5],
		7 => ['perfect', 5],
		8 => ['minor', 6],
		9 => ['major', 6],
		10 => ['minor', 7],
		11 => ['major', 7]
	];

	public function guide()
	{
		return $this->guide;
	}

	public function tones()
	{
		return str_split($this->tones);
	}

	public function intervals($index)
	{
		return $this->intervals[$index];
	}
}
