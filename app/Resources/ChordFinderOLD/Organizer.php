<?php

namespace App\Resources\ChordFinderOLD;

class Organizer
{
	protected $inversions, $enharmonicInversions;
	private $halfSteps = ['e+', 'b+'];

	public function __construct(ChordFinder $finder)
	{
		$this->inversions = [];
		$this->enharmonicInversions = [];
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

		if ($this->finder->enharmonicNotes) {
			for ($i=0; $i<count($this->finder->enharmonicNotes); $i++) {
				array_push($this->enharmonicInversions, $this->finder->enharmonicNotes);

				if ($this->isOctaveUp($this->enharmonicInversions[$i][0]))
					$this->enharmonicInversions[$i][0] = $this->removeOctave($this->enharmonicInversions[$i][0]);

				$root = $this->enharmonicInversions[$i][0];
				for ($j=1; $j<count($this->enharmonicInversions[$i]); $j++) {
					if ($this->interval($root, $this->enharmonicInversions[$i][$j])->in([10, 11, 12, 14]))
						$this->enharmonicInversions[$i][$j] = $this->removeOctave($this->enharmonicInversions[$i][$j]);

					if ($this->isOctaveUp($this->enharmonicInversions[$i][$j]))
						$this->enharmonicInversions[$i] = $this->moveToEnd($this->enharmonicInversions[$i], $j);
				}
				array_shift($this->finder->enharmonicNotes);
				array_push($this->finder->enharmonicNotes, $root);
			}
		}

		$this->finder->inversions = $this->inversions;
		$this->finder->enharmonicInversions = $this->enharmonicInversions;

		return $this;
	}

	public function clean()
	{
		$hasSharps = $hasFlats = $hasEnharmonics = false;
		
		$this->removeDuplicates();

		foreach ($this->finder->notes as $key => $note) {
			$this->finder->notes[$key] = str_replace('s', '+', $note);

			$hasSharps = $hasSharps ? $hasSharps : strhas($this->finder->notes[$key], '+');
			$hasFlats = $hasFlats ? $hasFlats : strhas($this->finder->notes[$key], '-');
			$hasEnharmonics = $hasEnharmonics ? 
				$hasEnharmonics : 
				$this->finder->notes[$key] == 'e+' && nextLetter($note) == 'F' || $this->finder->notes[$key] == 'b+' && nextLetter($note) == 'C';
			
			if ($this->isOctaveUp($note)) {
				$note = str_replace('2', '', $note);
				if ($index = array_search($note, $this->finder->notes))
					unset($this->finder->notes[$index]);
			}
		}

		if ($hasEnharmonics || ($hasSharps && $hasFlats)) {
			$count = count($this->finder->notes);
			$this->finder->enharmonicNotes = $this->finder->notes;

			foreach ($this->finder->notes as $key => $note) {
				if ($key < $count-1) {
					$nextNote = $this->finder->notes[$key+1];

					if (in_array($note, $this->halfSteps) || (strhas($note, '+') && strhas($nextNote, '-') && (nextLetter($note) == strtoupper($nextNote[0])))) {
						unset($this->finder->notes[$key]);
						unset($this->finder->enharmonicNotes[$key+1]);
					}
				}
			}
		}

		$this->sort();

		return $this;
	}

	public function removeDuplicates()
	{
		$this->finder->notes = array_unique($this->finder->notes);

		foreach ($this->finder->notes as $key => $note) {
			if (strhas($note, '2') && in_array($note[0], $this->finder->notes))
				unset($this->finder->notes[$key]);
		}

		$this->finder->notes = array_values($this->finder->notes);
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

	public function sort()
	{
		sort($this->finder->notes);
		$this->finder->notes = array_values($this->finder->notes);
	
		if ($this->finder->enharmonicNotes) {
			sort($this->finder->enharmonicNotes);
			$this->finder->enharmonicNotes = array_values($this->finder->enharmonicNotes);
		}
	}
}
