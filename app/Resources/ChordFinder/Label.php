<?php

namespace App\Resources\ChordFinder;

use App\Resources\ChordFinder\Traits\Notation;

class Label
{
	use Notation;

	protected $array;

	public function __construct(array $array)
	{
		$this->array = $array;	
	}

	public function analyser($chord)
	{
		return new Analyser($chord);
	}

	public function intervals()
	{
		foreach ($this->array as $index => $chord) {
			foreach ($chord['inversions'] as $key => $inversion) {
				$this->array[$index]['inversions'][$key]['intervals'] = $this->analyser($inversion['chord'])->intervals();
			}
		}

		return $this->array;
	}

	public function chords()
	{
		foreach ($this->array as $index => $chord) {
			foreach ($chord['inversions'] as $key => $inversion) {
				$this->array[$index]['inversions'][$key]['label'] = $this->read($inversion);
			}
		}

		return $this->array;
	}

	public function find($notes, $interval)
	{
		foreach ($notes['intervals'] as $note) {
			if ($note['interval'] == $interval)
				return $note;
		}
	}

	public function read($inversion)
	{
		return array_merge(
			$this->root($inversion),
			$this->core($inversion),
			$this->seventh($inversion),
			$this->sus($inversion),
			$this->extensions($inversion)
		);
	}
}
