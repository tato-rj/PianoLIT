<?php

namespace App\Resources\Technique\Traits;

trait Factory
{
	public function getFingering($hand, $type, $inversion = 0)
	{
		$key = $this->keys[$this->key][$this->name];

		if (is_array($key))
			return $key[$type][$inversion][$hand];

		return $this->keys[$key][$this->name][$type][$inversion][$hand];
	}

	public function getNotes()
	{
		return $this->keys[$this->key]['notes'];
	}

	public function getChord($length, $inversion = 0)
	{
		$scale = $this->keys[$this->key]['notes'];
		$triad = [];

		for ($i=0; $i<$length; $i++) {
			array_push($triad, $scale[$i*2]);
		}

		for ($i=0; $i<$inversion; $i++) {
			$first = array_shift($triad);
			array_push($triad, $first);
		}

		return array_values($triad);
	}
}
