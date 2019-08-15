<?php

namespace App\Resources\ChordFinder;

class Analyser
{
	protected $chord;

	public function __construct(array $chord)
	{
		$this->chord = $chord;	
	}

	public function intervals()
	{
		$intervals = [];
		$pastOctave = false;

		for ($i=1; $i<count($this->chord); $i++) {
			if ($pastOctave)
				$this->chord[$i] = $this->chord[$i] . '2';

			try {
				array_push($intervals, (new Interval($this->chord[0], $this->chord[$i]))->read());
			} catch (\Exception $e) {
				array_push($intervals, null);
			}

			if (end($intervals) && end($intervals)['interval'] >= 7)
				$pastOctave = true;
		}

		return $intervals;
	}
}
