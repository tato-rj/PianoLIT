<?php

namespace App\Resources\ChordFinderOLD;

class Worker
{
	protected $chord, $distances, $notes;
	protected $is_valid;

	public function __construct(ChordFinder $finder)
	{
		$this->is_valid = true;
		$this->finder = $finder;
	}
	
	public function interval($first, $second)
	{
		return new Interval($first, $second);
	}

	public function get($notes)
	{
		$this->chord = [];
		$this->intervals = [];
		$this->notes = $notes;

		foreach ($this->notes as $index => $root) {
			$subChord = $index > 0 ? $this->invert($index) : $this->notes;
			array_shift($subChord);

			foreach ($subChord as $note) {
				$this->saveInterval($root, $note);
				$this->saveDistance();
			}

			if (! $this->is_valid)
				return null;

			$this->chord['name'] =  $this->name($root);
			$this->chord['is_relevant'] = $this->isRelevant();
			$this->chord['is_main'] = $this->isMain();
			$this->chord['root'] = $root;
			$this->chord['intervals'] = $this->intervals;
			$this->chord['notes'] = array_values($this->notes);

			return $this->chord;
		}
	}

	public function name($root)
	{
		$firstLabel = $secondLabel = [];
		$third = $this->find(3);

		foreach ($this->chord as $index => $note) {
			if ($note['distance'] == 2) {
				array_push($firstLabel, is_null($third) ? ' sus'.$note['short'] : ' add'.$note['short']);
			} else if ($note['distance'] == 3 && $this->hasPerfectFifth()) {
				array_unshift($firstLabel, $note['type'] == 'minor' ? 'm' : 'M');
			} else if ($note['distance'] == 4) {
				array_push($firstLabel, is_null($third) ? ' sus'.$note['short'] : ' add'.$note['short']);
			} else if ($note['distance'] == 5 && $note['type'] != 'perfect') {
				array_unshift($firstLabel, substr($note['type'], 0, 3));
			} else if ($note['distance'] == 7) {
				array_push($firstLabel, $note['short']);
			} else if ($note['distance'] == 6 || $note['distance'] > 8) {
				array_push($secondLabel, str_replace('#', '', $note['short']));
			}
		}

		if (empty($secondLabel))
			$name = ucfirst($root) . implode(' ', $firstLabel);

		$name = ucfirst($root) . implode(' ', $firstLabel) . ' ' . implode(' ', $secondLabel);

		return rtrim(strtr($name, ['+' => '#', '-' => 'b']), ' ');
	}

	public function isRelevant()
	{
		$third = $this->find(3);
		$fourth = $this->find(4);
		$fifth = $this->find(5);
		
		if (! $this->hasValidThirdAndFifth($third, $fifth) || ! $this->checkDimSeventh())
			return false;

		return ! is_null($third) || $this->isSus($strict = true);
	}

	public function isMain()
	{
		if (! $this->isRelevant())
			return false;

		return ! is_null($this->find(5)) && substr_count($this->chord['name'], 'add') < 2;
	}

	public function isSus($strict = false)
	{
		$second = $this->find(2);
		$third = $this->find(3);
		$fourth = $this->find(4);

		if ($strict) {
			$hasSus = (! is_null($fourth) && $fourth['type'] == 'perfect') || (! is_null($second) && $second['type'] == 'perfect');
		} else {
			$hasSus = ! is_null($fourth) || ! is_null($second);
		}

		return is_null($third) && $hasSus;		
	}

	public function hasValidThirdAndFifth($third, $fifth)
	{
		if ($third['type'] == 'augmented' || $third['type'] == 'minor' && $fifth['type'] == 'augmented' || $third['type'] == 'major' && $fifth['type'] == 'diminished')
			return false;

		return true;
	}

	public function hasPerfectFifth()
	{
		$fifth = $this->find(5);

		return is_null($fifth) || $fifth['type'] == 'perfect';
	}

	public function checkDimSeventh()
	{
		$fifth = $this->find(5);
		$seventh = $this->find(7);

		if (! $seventh || $seventh['type'] != 'diminished')
			return true;

		return $fifth && $fifth['type'] == 'diminished';
		
	}

	public function find($interval)
	{
		$key = array_search($interval, $this->intervals);

		if ($key === false)
			return null;

		return $this->chord[$key];
	}

	public function invert($index)
	{
		$root = $this->notes[0];
		array_shift($this->notes);
		array_push($this->notes, $root);

		$this->notes = array_values($this->notes);

		return $this->notes;
	}

	public function saveInterval($root, $note)
	{
		try {
			array_push(
				$this->chord,
				$this->interval($root, $note)->analyse()
			);
		} catch (\Exception $e) {
			$this->is_valid = false;
		}
	}

	public function saveDistance()
	{
		$distance = (current($this->intervals) > end($this->chord)['distance']) ? 
			end($this->chord)['distance'] + 7 : 
			end($this->chord)['distance'];

		array_push(
			$this->intervals, 
			$distance
		);
	}
}
