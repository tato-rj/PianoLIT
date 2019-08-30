<?php

namespace App\Resources\Technique;

class Arpeggio extends Technique
{
	public function triad()
	{
		return [
			'type' => $this->type,
			'positions' => [
				[
					'name' => 'Root Position',
					'notes' => $this->getChord(3),
					'rh' => $this->getFingering('rh', 0),
					'lh' => $this->getFingering('lh', 0)
				],
				[
					'name' => '1st Inversion',
					'notes' => $this->getChord(3, 1),
					'rh' => $this->getFingering('rh', 1),
					'lh' => $this->getFingering('lh', 1)
				],
				[
					'name' => '2nd Inversion',
					'notes' => $this->getChord(3, 2),
					'rh' => $this->getFingering('rh', 2),
					'lh' => $this->getFingering('lh', 2)
				]
			]
		];
	}

	public function seventh()
	{
		//
	}
}
