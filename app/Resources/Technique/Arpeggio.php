<?php

namespace App\Resources\Technique;

class Arpeggio extends Technique
{
	public function triad()
	{
		return [
			'triad' => [
				'root position' => [
					'notes' => $this->getChord(3),
					'rh' => $this->getFingering('rh', 'triad', 0),
					'lh' => $this->getFingering('lh', 'triad', 0)
				],
				'1st inversion' => [
					'notes' => $this->getChord(3, 1),
					'rh' => $this->getFingering('rh', 'triad', 1),
					'lh' => $this->getFingering('lh', 'triad', 1)
				],
				'2nd inversion' => [
					'notes' => $this->getChord(3, 2),
					'rh' => $this->getFingering('rh', 'triad', 2),
					'lh' => $this->getFingering('lh', 'triad', 2)
				]
			]
		];
	}

	public function seventh()
	{
		//
	}
}
