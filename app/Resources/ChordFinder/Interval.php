<?php

namespace App\Resources\ChordFinder;

class Interval
{
	use Factory;
	protected $firstIndex, $secondIndex, $distance, $interval;

	public function find($first, $second, $octaveUp = false)
	{
		$this->getFirstIndex($first);
		$this->getSecondIndex($second);
		$this->getDistance($first, $second, $octaveUp);

		dd($this->getInterval($this->distance, $this->interval));

		$index = $this->secondIndex - $this->firstIndex;

		$type = $this->intervals($index)[0];
		$number = $octaveUp ? $this->intervals($index)[1] + 7 : $this->intervals($index)[1];
		$full = $type . ' ' . $number;
		$short = $type == 'major' ? $number : $type[0] . $number;

		return [
			'full' => $full,
			'short' => $short,
			'type' => $type,
			'number' => $number
		];
	}

	public function read($note)
	{
		$letter = $note[0];
		$accidental = strlen($note) > 1 ? $note[1] : null;
		$steps = strlen($note) - 1;

		return [
			'letter' => $letter,
			'accidental' => $accidental,
			'steps' => $steps
		];
	}

	public function applyAccidental($index, $note)
	{
		if ($note['accidental'] == '+')
			return $index + $note['steps'];

		if ($note['accidental'] == '-')
			return $index - $note['steps'];

		return $index;
	}

	public function getDistance($first, $second, $octaveUp = false)
	{
		$firstIndex = strpos($this->guide(), $first[0]);
		$scale = implode('', array_slice(str_split($this->guide()), $firstIndex));
		$distance = strpos($scale, $second[0]) + 1;
		$octave = $octaveUp ? 7 : 0;

		$this->distance = $distance + $octave;
	}

	public function getFirstIndex($note)
	{
		$index = $this->applyAccidental(
			array_search($note[0], $this->tones()),
			$this->read($note)
		);

		$this->firstIndex = $index;
	}

	public function getSecondIndex($note)
	{
		$index = $this->applyAccidental(
			array_search($note[0], array_slice($this->tones(), $this->firstIndex)),
			$this->read($note)
		) + $this->firstIndex;

		$this->secondIndex = $index;
		$this->interval = $this->secondIndex - $this->firstIndex;
	}
}
