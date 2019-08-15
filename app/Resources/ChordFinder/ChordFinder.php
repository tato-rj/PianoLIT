<?php

namespace App\Resources\ChordFinder;

class ChordFinder
{
	protected $notes, $results, $root, $bass;

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
		return new Ranking($this->results, $this->root);
	}

	public function root($root)
	{
		$this->root = $root;

		if ($this->root)
			$this->bass = str_replace('s', '+', $this->notes[0]);

		return $this;
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

		$query .= "root={$this->root}&";

		$query .= 'dev';

		return route('tools.chord-finder.analyse') . $query;
	}

	public function validate()
	{
		$this->notes = $this->cleaner()
							->setMinimum(3)
							->lowercase()
							->removeDuplicates()
							->fixSharps()
							->splitEnharmonics()
							->sort($this->root)
							->getNotes();

		return $this;
	}

	public function analyse()
	{

			$this->getInversions();
			$this->results = $this->label()->intervals();

			if ($this->root) {
				$this->results = $this->label()->strict($this->bass)->chords();
			} else {
				$this->results = $this->validator()->removeImpossible()->get();
				$this->results = $this->validator()->addNinth()->get();
				$this->results = $this->validator()->addEleventh()->get();
				$this->results = $this->validator()->addThirteenth()->get();
				$this->results = $this->validator()->fixAddedIntervals()->get();
				$this->results = $this->label()->chords();
			}
			
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
		$this->validator()->ready();
		
		return $this->results;
	}

	public function getInversions()
	{
		$results = [];

		if ($this->root) {
			$results[0]['notes'] = $this->notes[0];
			$results[0]['inversions'] = $this->inversion($this->notes[0])->defined($this->root);
		} else {
			foreach ($this->notes as $index => $notes) {
				$results[$index]['notes'] = $notes;
				$results[$index]['inversions'] = $this->inversion($notes)->all();
			}			
		}

		$this->results = $results;
	}
}
