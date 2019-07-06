<?php

namespace App\Resources\ChordFinder;

class ChordFinder
{
	use Factory;

	public $notes, $full_name, $short_name, $chord, $chords;

	public function __construct()
	{
		$this->chords = [];
		$this->validator = new Validator($this);
	}
	
	public function interval($first, $second, $octaveUp = false)
	{
		return new Interval($first, $second, $octaveUp);
	}

	public function take(array $notes)
	{
		$this->chord = [];

		$this->notes = $notes;

		$this->validator->run();

		return $this;		
	}

	public function analyse()
	{
		$this->organize();

		$this->getChords();

		return [
			'notes' => $this->notes,
			'chords' => $this->chords
		];
	}

	public function organize()
	{
		$this->notes = array_unique($this->notes);

		sort($this->notes);
	}

	public function invert($index)
	{
		$root = $this->notes[0];
		array_shift($this->notes);
		array_push($this->notes, $root);
		$this->notes = array_values($this->notes);

		return $this->notes;
	}

	public function getChords()
	{
		$inversion = [];
		$intervals = [];

		foreach ($this->notes as $index => $root) {
			$subChord = $index > 0 ? $this->invert($index) : $this->notes;
			array_shift($subChord);
			foreach ($subChord as $note) {
				$interval = $this->interval($root, $note)->analyse();
				array_push($inversion, $interval);
				$distance = (current($intervals) > $interval['distance']) ? $interval['distance'] + 7 : $interval['distance'];

				array_push($intervals, $distance);
			}
			$inversion['root'] = $root;
			$inversion['intervals'] = $intervals;
			$inversion['info'] = $this->info($inversion);
	
			if ($inversion['info']['relevant'])
				$this->chords[$index] = $inversion;
	
			$inversion = [];
			$intervals = [];
		}

		foreach ($this->chords as $index => $chord) {
			if ($chord['info']['is_main']) {
				$mainChord = $chord;
				unset($this->chords[$index]);
				array_unshift($this->chords, $mainChord);
			}
		}

		$this->chords = array_values($this->chords);
		sort($this->notes);
	}

	public function getShortType($type)
	{
		if ($type == 'diminished')
			return 'dim';
		
		if ($type == 'augmented')
			return 'aug';

		if ($type == 'major')
			return '';
		
		return $type[0];	
	}

	public function info($chord)
	{
		$fullType = $shortType = $note = '';
		$relevant = true;
		$is_main = false;
		$root = $this->nameForHumans($chord['root']);
		$chordIntervals = $this->chordIntervals($chord);

		if ($this->hasThirdAndFifth($chordIntervals)) {
			$fullType .= $chordIntervals['fifth']['type'] == 'perfect' ? $chordIntervals['third']['type'] : $chordIntervals['fifth']['type'];
			$note = 'root position';
			$is_main = true;
		} elseif ($this->hasThirdOrFifth($chordIntervals)) {
			$fullType .= $chordIntervals['fifth'] ? $chordIntervals['fifth']['type'] : $chordIntervals['third']['type'];
		} else {
			$relevant = false;
		}

		if ($this->isFirstInversion($chordIntervals))
			$note = 'might be the 1st inversion of a ' . $chordIntervals['sixth']['note'] . ' chord';

		$fullType = str_replace('perfect', '', $fullType);
		
		if ($fullType == 'major') {
			$shortType = 'M';
		} elseif ($fullType == 'minor') {
			$shortType = 'm';
		} else {
			$shortType = substr($fullType, 0, 3);
		}

		if ($this->isSus($chordIntervals)) {
			if ($chordIntervals['second']) {
				$fullType .= 'sus2';
				$shortType .= 'sus2';
			}
			if ($chordIntervals['fourth']) {
				$fullType .= 'sus4'; 
				$shortType .= 'sus4';
			}
		}

		if ($this->hasSeventh($chordIntervals)) {
			$fullType .= ' ' . $chordIntervals['seventh']['short']; 
			$shortType .= $chordIntervals['seventh']['short'];
		}

		if ($this->hasSixth($chordIntervals)) {
			$fullType .= ' ' . $chordIntervals['sixth']['short']; 
			$shortType .= $chordIntervals['sixth']['short'];
		}

		return [
			'full_name' => $root . ' ' . $fullType,
			'short_name' => $root . $shortType,
			'note' => $note,
			'is_main' => $is_main,
			'relevant' => $relevant
		];
	}

	public function find($chord, $interval)
	{
		$index = array_search($interval, $chord['intervals']);

		if ($index === false)
			return null;

		return $chord[$index];
	}

	public function chordIntervals($chord)
	{
		return [
			'second' => $this->find($chord, 2),
			'third' => $this->find($chord, 3),
			'fourth' => $this->find($chord, 4),
			'fifth' => $this->find($chord, 5),
			'sixth' => $this->find($chord, 6),
			'seventh' => $this->find($chord, 7)
		];
	}

	public function hasThirdAndFifth($chordIntervals)
	{
		return $chordIntervals['third'] && $chordIntervals['fifth'];
	}

	public function hasThirdOrFifth($chordIntervals)
	{
		return $chordIntervals['third'] || $chordIntervals['fifth'];
	}

	public function isFirstInversion($chordIntervals)
	{
		return $chordIntervals['third'] && $chordIntervals['sixth'] && !$chordIntervals['fifth'];
	}

	public function hasSixth($chordIntervals)
	{
		return $chordIntervals['sixth'];
	}

	public function hasSeventh($chordIntervals)
	{
		return $chordIntervals['seventh'];
	}

	public function isSus($chordIntervals)
	{
		return $chordIntervals['second'] || $chordIntervals['fourth'];
	}

	public function nameForHumans($note)
	{
		$note = str_replace('+', '#', $note);
		$note = str_replace('-', 'b', $note);

		return ucfirst($note);
	}

	// public function findInterval($note, $interval)
	// {
	// 	$array = str_split($this->guide());
	// 	$index = strpos($this->guide(), $note[0]) - 1;
	// 	$ext = (strlen($note) == 1) ? '' : substr($note, strpos($note, $note[0]) + 1);

	// 	return $array[$index + $interval] . $ext;
	// }

	// public function eliminateIntervals($intervals)
	// {
	// 	while ($index = $this->hasAny($intervals)) {
	// 		$note = $this->notes[$index];
	// 		unset($this->notes[$index]);
	// 		array_unshift($this->notes, $note);
	// 		$this->notes = array_values($this->notes);
	// 	}
	// }

	// public function hasAny($intervals)
	// {
	// 	$returnIndex = false;

	// 	foreach ($intervals as $interval) {
	// 		foreach ($this->notes as $index => $note) {
	// 			if ($index == count($this->notes) - 1)
	// 				break;

	// 			if ($this->findInterval($note[0], $interval) == $this->next($index)) {
	// 				$returnIndex = $index + 1;
	// 				break;
	// 			}
	// 		}

	// 		if ($returnIndex)
	// 			break;
	// 	}

	// 	return $returnIndex;
	// }

	// public function next($index, $plain = true)
	// {
	// 	return $plain ? $this->notes[$index + 1][0] : $this->notes[$index + 1];
	// }

	// public function getChord()
	// {
	// 	for ($i=0; $i<count($this->notes); $i++) {
	// 		$this->chord[$i]['note'] = $this->notes[$i];
	// 		$this->chord[$i]['interval'] = $this->interval($this->notes[0], $this->notes[$i])->analyse();
	// 	}	
	// }
}
