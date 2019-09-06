<?php

namespace App\Resources\Technique;

class Scale extends Technique
{
	public function diatonic()
	{
		return [
			'type' => $this->type,
			'modes' => $this->getModes()
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
