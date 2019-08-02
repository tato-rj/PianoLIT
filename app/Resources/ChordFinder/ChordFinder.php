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

	public function ranking()
	{
		return new Ranking($this->results);
	}

	public function take($notes)
	{
		$this->notes = $notes;

		return $this;
	}

	public function debug()
	{
		$query = '?';

		foreach ($this->notes as $note) {
			$note = str_replace('+', 's', $note);
			$query .= 'notes[]=' . $note . '&';	
		}

		$query .= 'dev';

		return route('tools.chord-finder.analyse') . $query;
	}

	public function validate()
	{
		$this->notes = $this->cleaner()
							->setMinimum(2)
							->lowercase()
							->removeDuplicates()
							->fixSharps()
							->splitEnharmonics()
							->sort()
							->getNotes();

		return $this;
	}

	public function analyse()
	{
		$this->getInversions();
		$this->results = $this->label()->intervals();
		$this->results = $this->validator()->removeImpossible()->get();
		$this->results = $this->validator()->addNinth()->get();
		$this->results = $this->label()->chords();
		$this->results = array_values($this->results);

		return $this;
	}

	public function ranked()
	{
		$this->results = $this->ranking()->apply();

		return $this;
	}

	public function get()
	{
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
