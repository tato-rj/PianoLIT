<?php

namespace App\Resources\ChordFinder;

class ChordFinder
{
	use Factory;

	protected $notes, $name;

	public function take(array $notes)
	{
		$this->notes = $notes;

		return $this;		
	}

	public function analyse()
	{
		(new Validator)->run($this);

		$this->organize();

		$this->setName();

		return [
			'notes' => $this->notes,
			'name' => $this->name
		];
	}

	public function interval()
	{
		return new Interval;
	}

	public function findInterval($note, $interval)
	{
		$array = str_split($this->guide());
		$index = strpos($this->guide(), $note[0]) - 1;
		$ext = (strlen($note) == 1) ? '' : substr($note, strpos($note, $note[0]) + 1);

		return $array[$index + $interval] . $ext;
	}

	public function setName()
	{
		$root = $this->notes[0][0];

		$this->name = ucfirst($root) . ' major';	
	}

	public function organize()
	{
		$this->notes = array_unique($this->notes);

		sort($this->notes);

		$this->eliminateIntervals([2,4,6]);
	}

	public function eliminateIntervals($intervals)
	{
		while ($index = $this->hasAny($intervals)) {
			$note = $this->notes[$index];
			unset($this->notes[$index]);
			array_unshift($this->notes, $note);
			$this->notes = array_values($this->notes);
		}
	}

	public function hasAny($intervals)
	{
		$returnIndex = false;

		foreach ($intervals as $interval) {
			foreach ($this->notes as $index => $note) {
				if ($index == count($this->notes) - 1)
					break;

				if ($this->findInterval($note[0], $interval) == $this->next($index)) {
					$returnIndex = $index + 1;
					break;
				}
			}

			if ($returnIndex)
				break;
		}

		return $returnIndex;
	}

	public function next($index, $plain = true)
	{
		return $plain ? $this->notes[$index + 1][0] : $this->notes[$index + 1];
	}
}
