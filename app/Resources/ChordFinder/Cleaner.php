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
			abort(422, 'Please select a minimum of '.$minimum.' notes');

		return $this;
	}

	public function lowercase()
	{
		$this->notes = array_map('strtolower', $this->notes);

		return $this;		
	}

	public function removeDuplicates()
	{
		$copy = array_unique($this->notes);

		$this->notes = array_values($copy);

		return $this;
	}

	public function fixSharps()
	{
		$copy = $this->notes;

		foreach ($copy as $index => $note) {
			if (strhas($note, 's'))
				$copy[$index] = str_replace('s', '+', $note);
		}

		$this->notes = $copy;

		return $this;
	}

	public function capitalize()
	{
		$this->notes = array_map('ucfirst', $this->notes);

		return $this;		
	}

	public function sort($hasRoot, $tool)
	{
		if ($hasRoot && $tool != 'button')
			return $this;
		
		$copy = $this->notes;

		if (count($copy) && is_array($copy[0])) {
			foreach ($copy as $index => $array) {
				unset($copy[$index]);
				sort($array);
				array_push($copy, $array);
			}
		} else {
			sort($copy);
		}

		$this->notes = array_values($copy);

		return $this;
	}

	public function getNotes()
	{
		return [$this->notes];
	}
}
