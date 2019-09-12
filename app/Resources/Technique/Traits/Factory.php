<?php

namespace App\Resources\Technique\Traits;

trait Factory
{
	public function getFingering($hand, $inversion = 0)
	{
		$key = $this->keys[$this->key][$this->name];

		if (is_array($key)) {
			$fingering = $key[$this->type][$inversion][$hand];
		} else {
			$fingering =  $this->keys[$key][$this->name][$this->type][$inversion][$hand];			
		}

		$up = $fingering;

		array_pop($up);

		return array_merge($fingering, array_reverse($up)); 
	}

	public function getMajor()
	{
		$notes = $this->keys[$this->key]['notes'];
		array_push($notes, $notes[0]);

		return [
			[
				'key' => $this->keys[$this->key]['label'] . ' scale',
				'name' => 'major',
				'notes' => $notes,
				'up_down' => $this->full($notes),
				'rh' => $this->getFingering('rh'),
				'lh' => $this->getFingering('lh')
			]
		];
	}

	public function full($notes, $original = null)
	{
		$copy = $notes;

		array_pop($copy);

		if ($original)
			array_pop($original);

		$reverse = array_reverse($original ?? $copy);

		return array_merge($notes, $reverse);
	}

	public function getModes()
	{
		if (! $this->isMinor())
			return $this->getMajor();

		$natural = $this->keys[$this->key]['notes'];
		$harmonic = [$natural[0], $natural[1], $natural[2], $natural[3], $natural[4], $natural[5], $this->stepUp($natural[6])];
		$melodic = [$natural[0], $natural[1], $natural[2], $natural[3], $natural[4], $this->stepUp($natural[5]), $this->stepUp($natural[6])];
		array_push($natural, $natural[0]);
		array_push($harmonic, $natural[0]);
		array_push($melodic, $natural[0]);

		return [
			[
				'key' => explode(' ', $this->keys[$this->key]['label'])[0] . ' natural ' . explode(' ', $this->keys[$this->key]['label'])[1],
				'name' => 'natural',
				'notes' => $natural,
				'up_down' => $this->full($natural),
				'rh' => $this->getFingering('rh'),
				'lh' => $this->getFingering('lh')
			], 
			[
				'key' => explode(' ', $this->keys[$this->key]['label'])[0] . ' harmonic ' . explode(' ', $this->keys[$this->key]['label'])[1],
				'name' => 'harmonic',
				'notes' => $harmonic,
				'up_down' => $this->full($harmonic),
				'rh' => $this->getFingering('rh'),
				'lh' => $this->getFingering('lh')
			],
			[
				'key' => explode(' ', $this->keys[$this->key]['label'])[0] . ' melodic ' . explode(' ', $this->keys[$this->key]['label'])[1],
				'name' => 'melodic',
				'notes' => $melodic,
				'up_down' => $this->full($melodic, $natural),
				'rh' => $this->getFingering('rh'),
				'lh' => $this->getFingering('lh')
			], 
		];
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
