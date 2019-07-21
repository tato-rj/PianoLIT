<?php

namespace App\Resources\ChordFinder;

class ChordFinder
{
	protected $notes;

	public function __construct()
	{

	}

	public function cleaner()
	{
		return new Cleaner($this->notes);
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
							->capitalize()
							->fixSharps()
							->getNotes();
	}

	public function analyse()
	{
		$this->validate();
		// return chords
	}
}
