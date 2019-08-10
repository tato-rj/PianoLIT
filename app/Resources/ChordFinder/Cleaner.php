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

	public function splitEnharmonics()
	{
		$copy = $this->notes;

		foreach ($this->notes as $index => $note) {
			if (array_key_exists($index+1, $copy)) {
				if ((new Interval($note, $copy[$index+1]))->isEnharmonic()) {
					unset($this->notes[$index]);
					unset($copy[$index+1]);
				}
			}
		}

		$this->notes = ($copy != $this->notes) ? [
			array_values($this->notes), 
			array_values($copy)
		] : [$copy];

		$this->splitWhiteEnharmonics();

		$this->removeDuplicateLetters();

		return $this;
	}

	public function capitalize()
	{
		$this->notes = array_map('ucfirst', $this->notes);

		return $this;		
	}

	public function sort()
	{
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

	public function splitWhiteEnharmonics()
	{
		$copy = $this->notes;

		foreach ($copy as $key => $array) {
			foreach ($array as $index => $note) {
				$enharmonic = (new Interval($note))->getWhiteEnharmonic();
				if ($enharmonic != $note)
					$array[$index] = $enharmonic;
			}

			if ($copy[$key] != $array && ! array_has_array($this->notes, $array)) {
				array_push($this->notes, array_unique($array));
			}
		}
	}

	public function removeDuplicateLetters()
	{
		$copy = $this->notes;

		foreach ($copy as $key => $array) {
			foreach ($array as $index => $note) {
				if (strlen($note) == 1) {
					if (in_array($note . '+', $array) || in_array($note . '-', $array))
						unset($copy[$key]);
				}
			}
		}
		
		$this->notes = array_values($copy);
	}

	public function getNotes()
	{
		return $this->notes;
	}
}
