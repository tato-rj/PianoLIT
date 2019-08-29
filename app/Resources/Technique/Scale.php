<?php

namespace App\Resources\Technique;

class Scale extends Technique
{
	public function diatonic()
	{
		return [
			'type' => $this->type,
			'notes' => $this->getNotes(),
			'rh' => $this->getFingering('rh'),
			'lh' => $this->getFingering('lh')
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
