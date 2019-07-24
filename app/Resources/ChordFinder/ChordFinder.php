<?php

namespace App\Resources\ChordFinder;

class ChordFinder
{
	protected $notes, $results;

	public function cleaner()
	{
		return new Cleaner($this->notes);
	}

	public function inversion($chord)
	{
		return new Inversion($chord);
	}

	public function analyser($chord)
	{
		return new Analyser($chord);
	}

	public function take($notes)
	{
		$this->notes = $notes;

		return $this;
	}

	public function validate()
	{		
		$this->notes = $this->cleaner()
							->setMinimum(2)
							->removeDuplicates()
							->fixSharps()
							->splitEnharmonics()
							->sort()
							->getNotes();

		return $this;
	}

	public function get()
	{
		$this->getInversions();
		$this->getLabels();

		return $this->results;
	}

	public function getLabels()
	{
		foreach ($this->results as $index => $chord) {
			foreach ($chord['inversions'] as $key => $inversion) {
				$this->results[$index]['inversions'][$key]['intervals'] = $this->analyser($inversion['chord'])->intervals();
			}
		}
	}

	public function getInversions()
	{
		$results = [];

		foreach ($this->notes as $index => $notes) {
			$results[$index]['notes'] = $notes;
			$results[$index]['inversions'] = $this->inversion($notes)->all();
		}

		$this->results = $results;
	}
}
