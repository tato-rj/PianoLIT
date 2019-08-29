<?php

namespace App\Resources\Technique;

class Scale extends Technique
{
	public function diatonic()
	{
		return [
			'diatonic' => [
				'notes' => $this->getNotes(),
				'rh' => $this->getFingering('rh', 'diatonic'),
				'lh' => $this->getFingering('lh', 'diatonic')
			]
		];
	}

	public function pentatonic()
	{
		//
	}

	public function wholetone()
	{
		//
	}
}
