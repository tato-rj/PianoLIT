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
					'key' => $this->keys[$this->key]['label'] . ' arpeggio',
					'name' => 'Root Position',
					'notes' => $this->getChord(3),
					'rh' => $this->getFingering('rh', 0),
					'lh' => $this->getFingering('lh', 0)
				],
				[
					'key' => $this->keys[$this->key]['label'] . ' arpeggio',
					'name' => '1st Inversion',
					'notes' => $this->getChord(3, 1),
					'rh' => $this->getFingering('rh', 1),
					'lh' => $this->getFingering('lh', 1)
				],
				[
					'key' => $this->keys[$this->key]['label'] . ' arpeggio',
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
