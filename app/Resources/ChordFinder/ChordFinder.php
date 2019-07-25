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

	public function validator()
	{
		return new Validator($this->results);
	}

	public function label()
	{
		return new Label($this->results);
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
		$this->results = $this->label()->intervals();
		$this->results = $this->validator()->removeImpossible()->get();
		$this->results = $this->label()->chords();

		return $this->results;
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
