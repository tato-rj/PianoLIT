<?php

namespace App\Resources\ChordFinder;

trait Factory
{
	private $guide = 'abcdefgabcdefg';
	private $tones = '+a+bc+d+ef+g+a+bc+d+ef+g+';
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

	public function guide()
	{
		return $this->guide;
	}

	public function tones()
	{
		return str_split($this->tones);
	}
}
