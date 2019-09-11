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
					'up_down' => $this->getChord(3),
					'notes' => $this->getChord(3),
					'rh' => $this->getFingering('rh', 0),
					'lh' => $this->getFingering('lh', 0)
				],
				[
					'key' => $this->keys[$this->key]['label'] . ' arpeggio',
					'name' => '1st Inversion',
					'up_down' => $this->getChord(3, 1),
					'notes' => $this->getChord(3, 1),
					'rh' => $this->getFingering('rh', 1),
					'lh' => $this->getFingering('lh', 1)
				],
				[
					'key' => $this->keys[$this->key]['label'] . ' arpeggio',
					'name' => '2nd Inversion',
					'up_down' => $this->getChord(3, 2),
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
