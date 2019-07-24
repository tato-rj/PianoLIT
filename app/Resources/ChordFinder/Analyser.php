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

		for ($i=1; $i<count($this->chord); $i++) {
			try {
				array_push($intervals, (new Interval($this->chord[0], $this->chord[$i]))->read());
			} catch (\Exception $e) {
				array_push($intervals, null);
			}
		}

		return $intervals;
	}

	public function name()
	{
		return 'Stella';
	}
}
