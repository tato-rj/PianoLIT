<?php

namespace App\Resources\ChordFinderOLD;

class Interval
{
	use Factory;
	protected $firstNote, $secondNote, $firstIndex, $secondIndex, $distance, $intervalType, $octaveUp;

	public function __construct($firstNote, $secondNote)
	{
		$octaveUp = is_numeric(substr($secondNote, -1));

		if (is_numeric(substr($firstNote, -1)))
			$firstNote = substr($firstNote, 0, -1);

		if (is_numeric(substr($secondNote, -1)))
			$secondNote = substr($secondNote, 0, -1);

		$this->firstNote = $firstNote;
		$this->secondNote = $secondNote;
		$this->octaveUp = $octaveUp;
	}

	public function analyse()
	{
		$this->getFirstIndex();
		$this->getSecondIndex();
		$this->getDistance();
		$this->getIntervalType();

		$full = $this->intervalType . ' ' . $this->distance;
		$short = $this->distance;

		if ($this->intervalType == 'major' && $this->distance != 2) {
			$short = '#' . $short;
		} elseif ($this->intervalType == 'minor') {
			$short = 'b' . $short;
		} elseif ($this->intervalType == 'diminished') {
			$short = 'dim' . $short;
		} elseif ($this->intervalType == 'augmented') {
			$short = 'aug' . $short;
		}

		return [
			'note' => ucfirst($this->secondNote),
			'full' => $full,
			'short' => $short,
			'type' => $this->intervalType,
			'distance' => $this->distance
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

	public function getDistance()
	{
		$firstIndex = strpos($this->guide(), $this->firstNote[0]);
		$scale = implode('', array_slice(str_split($this->guide()), $firstIndex));
		$distance = strpos($scale, $this->secondNote[0]) + 1;
	
		$this->ignoreTwelvethInterval($distance);
	
		$octave = $this->octaveUp ? 7 : 0;

		$this->distance = $distance + $octave;
	}

	public function ignoreTwelvethInterval($distance)
	{
		if ($this->octaveUp)
			$this->octaveUp = ($distance != 5 || $distance != 3);
	}

	public function getIntervalType()
	{
		$index = $this->octaveUp ? $this->distance - 7 : $this->distance;
		
		try {
			$type = $index == 1 ? 'root' : $this->intervals[$index][$this->secondIndex - $this->firstIndex];
		} catch (\Exception $e) {
			abort(422, 'The interval between ' . $this->firstNote . ' and ' . $this->secondNote . ' could not be named');
		}

		$this->intervalType = $type;
	}

	public function getFirstIndex()
	{
		$index = $this->applyAccidental(
			array_search($this->firstNote[0], $this->tones()),
			$this->read($this->firstNote)
		);

		$this->firstIndex = $index;
	}

	public function getSecondIndex()
	{
		$index = $this->applyAccidental(
			array_search($this->secondNote[0], array_slice($this->tones(), $this->firstIndex)),
			$this->read($this->secondNote)
		) + $this->firstIndex;

		$this->secondIndex = $index;
	}

	public function in($intervals)
	{
		$this->getFirstIndex();
		$this->getSecondIndex();
		$this->getDistance();
		$response = false;
		
		foreach ($intervals as $interval) {
			if ($this->distance == $interval) {
				$response = true;
				break;
			}
		}
		
		return $response;
	}
}
