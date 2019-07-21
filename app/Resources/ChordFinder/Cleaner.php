<?php

namespace App\Resources\ChordFinder;

class Cleaner
{
	protected $notes;

	public function __construct($notes)
	{
		$this->notes = $notes;
	}

	public function setMinimum($minimum)
	{
		if (! $this->notes || count($this->notes) < $minimum)
			abort(422, 'Can\'t make a chord with less than '.$minimum.' notes');

		return $this;
	}

	public function removeDuplicates()
	{
		$copy = array_unique($this->notes);

		foreach ($copy as $index => $note) {
			if (strhas($note, '2') && in_array($note[0], $copy))
				unset($copy[$index]);
		}

		$this->notes = array_values($copy);

		return $this;
	}

	public function capitalize()
	{
		$this->notes = array_map('ucfirst', $this->notes);

		return $this;		
	}

	public function sort()
	{
		sort($this->notes);

		return $this;
	}

	public function fixSharps()
	{
		foreach ($this->notes as $index => $note) {
			if (strhas($note, 's'))
				$this->notes[$index] = str_replace('s', '+', $note);
		}

		return $this;		
	}

	public function getNotes()
	{
		return $this->notes;
	}
}
