<?php

namespace App\Resources\ChordFinder;

class Organizer
{
	protected $inversions;

	public function __construct(ChordFinder $finder)
	{
		$this->inversions = [];
		$this->finder = $finder;
	}
	
	public function interval($first, $second)
	{
		return new Interval($first, $second);
	}

	public function run()
	{
		for ($i=0; $i<count($this->finder->notes); $i++) {
			array_push($this->inversions, $this->finder->notes);

			if ($this->isOctaveUp($this->inversions[$i][0]))
				$this->inversions[$i][0] = $this->removeOctave($this->inversions[$i][0]);

			$root = $this->inversions[$i][0];
			for ($j=1; $j<count($this->inversions[$i]); $j++) {
				if ($this->interval($root, $this->inversions[$i][$j])->in([10, 11, 12, 14]))
					$this->inversions[$i][$j] = $this->removeOctave($this->inversions[$i][$j]);

				if ($this->isOctaveUp($this->inversions[$i][$j]))
					$this->inversions[$i] = $this->moveToEnd($this->inversions[$i], $j);
			}
			array_shift($this->finder->notes);
			array_push($this->finder->notes, $root);
		}

		$this->finder->inversions = $this->inversions;

		return $this;
	}

	public function clean()
	{
		$this->finder->notes = array_unique($this->finder->notes);
		foreach ($this->finder->notes as $key => $note) {
			$this->finder->notes[$key] = str_replace('s', '+', $note);
			if ($this->isOctaveUp($note)) {
				$note = str_replace('2', '', $note);
				if ($index = array_search($note, $this->finder->notes))
					unset($this->finder->notes[$index]);
			}
		}

		sort($this->finder->notes);

		$this->finder->notes = array_values($this->finder->notes);

		return $this;
	}

	public function isOctaveUp($note)
	{
		return strpos($note, '2') !== false;
	}

	public function removeOctave($note)
	{
		return str_replace('2', '', $note);
	}

	public function moveToEnd($notes, $index)
	{
		$note = $notes[$index];
		unset($notes[$index]);
		array_push($notes, $note);

		return $notes;
	}
}
