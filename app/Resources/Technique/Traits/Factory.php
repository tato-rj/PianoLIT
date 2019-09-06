<?php

namespace App\Resources\Technique\Traits;

trait Factory
{
	public function getFingering($hand, $inversion = 0)
	{
		$key = $this->keys[$this->key][$this->name];

		if (is_array($key))
			return $key[$this->type][$inversion][$hand];

		return $this->keys[$key][$this->name][$this->type][$inversion][$hand];
	}

	public function getNotes()
	{
		$notes = $this->keys[$this->key]['notes'];
		array_push($notes, $notes[0]);

		return [$notes];
	}

	public function getModes()
	{
		$notes = $this->keys[$this->key]['notes'];
		$harmonic = [$notes[0], $notes[1], $notes[2], $notes[3], $notes[4], $notes[5], $this->stepUp($notes[6])];
		$melodic = [$notes[0], $notes[1], $notes[2], $notes[3], $notes[4], $this->stepUp($notes[5]), $this->stepUp($notes[6])];
		array_push($notes, $notes[0]);
		array_push($harmonic, $notes[0]);
		array_push($melodic, $notes[0]);

		return ['natural' => $notes, 'harmonic' => $harmonic, 'melodic' => $melodic];
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

		array_push($triad, $triad[0]);

		return array_values($triad);
	}

	public function stepUp($note)
	{
		return strhas($note, '-') ? substr($note, 0, -1) : $note . '+';
	}
}
