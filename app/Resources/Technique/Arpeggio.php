<?php

namespace App\Resources\Technique;

class Arpeggio extends Technique
{
	public function triad()
	{
		return [
			'type' => $this->type,
			'root position' => [
				'notes' => $this->getChord(3),
				'rh' => $this->getFingering('rh', 0),
				'lh' => $this->getFingering('lh', 0)
			],
			'1st inversion' => [
				'notes' => $this->getChord(3, 1),
				'rh' => $this->getFingering('rh', 1),
				'lh' => $this->getFingering('lh', 1)
			],
			'2nd inversion' => [
				'notes' => $this->getChord(3, 2),
				'rh' => $this->getFingering('rh', 2),
				'lh' => $this->getFingering('lh', 2)
			]
		];
	}

	public function seventh()
	{
		//
	}
}
