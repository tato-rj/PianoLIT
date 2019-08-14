<?php

namespace App\Resources\ChordFinder;

class Validator
{
	protected $array;

	public function __construct(array $array)
	{
		$this->array = $array;
	}

	public function ready()
	{
		if (empty($this->array['chords']))
			abort(422, 'Sorry, we couldn\'t create any chord with these notes.');

		return true;
	}

	public function removeImpossible()
	{
		$copy = $this->array;

		foreach ($this->array as $index => $result) {
			foreach ($result['inversions'] as $key => $inversion) {
				if ($this->filters($inversion['intervals']))
					unset($copy[$index]['inversions'][$key]);
			}
		}

		foreach ($copy as $index => $result) {
			if (empty($result['inversions'])) {
				unset($copy[$index]);
			} else {
				$copy[$index]['inversions'] = array_values($result['inversions']);
			}
		}

		$this->array = $copy;

		return $this;
	}

	public function addNinth()
	{
		$copy = $this->array;
		$newInversion = [];

		foreach ($copy as $index => $notes) {
			foreach ($notes['inversions'] as $key => $inversion) {
				foreach ($inversion['intervals'] as $item => $interval) {
					if ($interval['interval'] == 2) {
						$newInversion = $copy[$index]['inversions'][$key];
						$newInterval = $newInversion['intervals'][$item]['interval'] + 7;
						$newStep = $newInversion['intervals'][$item]['steps'] + 12;

						$newInversion['intervals'][$item]['name'] = str_replace(
							$newInversion['intervals'][$item]['interval'], $newInterval, 
							$newInversion['intervals'][$item]['name']
						);
						$newInversion['intervals'][$item]['interval'] = $newInterval;
						$newInversion['intervals'][$item]['steps'] = $newStep;

						$tempInterval = $newInversion['intervals'][$item];
						unset($newInversion['intervals'][$item]);
						array_push($newInversion['intervals'], $tempInterval);
						$newInversion['intervals'] = array_values($newInversion['intervals']);

						$tempNote = $inversion['chord'][$item+1];
						unset($inversion['chord'][$item+1]);
						array_push($inversion['chord'], $tempNote);
						$newInversion['chord'] = array_values($inversion['chord']);
					}
				}
				
				if (!empty($newInversion)) {
					array_push($copy[$index]['inversions'], $newInversion);
					$newInversion = [];
				}
			}
		}

		$this->array = $copy;

		return $this;
	}

	public function addEleventh()
	{
		$copy = $this->array;
		$newInversion = [];

		foreach ($copy as $index => $notes) {
			foreach ($notes['inversions'] as $key => $inversion) {
				foreach ($inversion['intervals'] as $item => $interval) {
					if ($interval['interval'] == 4) {
						$newInversion = $copy[$index]['inversions'][$key];
						$newInterval = $newInversion['intervals'][$item]['interval'] + 7;
						$newStep = $newInversion['intervals'][$item]['steps'] + 12;

						$newInversion['intervals'][$item]['name'] = str_replace(
							$newInversion['intervals'][$item]['interval'], $newInterval, 
							$newInversion['intervals'][$item]['name']
						);
						$newInversion['intervals'][$item]['interval'] = $newInterval;
						$newInversion['intervals'][$item]['steps'] = $newStep;

						$tempInterval = $newInversion['intervals'][$item];
						unset($newInversion['intervals'][$item]);
						array_push($newInversion['intervals'], $tempInterval);
						$newInversion['intervals'] = array_values($newInversion['intervals']);

						$tempNote = $inversion['chord'][$item+1];
						unset($inversion['chord'][$item+1]);
						array_push($inversion['chord'], $tempNote);
						$newInversion['chord'] = array_values($inversion['chord']);
					}
				}

				if (!empty($newInversion)) {
					array_push($copy[$index]['inversions'], $newInversion);
					$newInversion = [];
				}			
			}
		}

		$this->array = $copy;

		return $this;
	}

	public function addThirteenth()
	{
		$copy = $this->array;
		$newInversion = [];

		foreach ($copy as $index => $notes) {
			foreach ($notes['inversions'] as $key => $inversion) {
				foreach ($inversion['intervals'] as $item => $interval) {
					
					$isLast = count($inversion['intervals']) == $item +1;

					if ($interval['interval'] == 6 && ! $isLast) {
						$newInversion = $copy[$index]['inversions'][$key];
						$newInterval = $newInversion['intervals'][$item]['interval'] + 7;
						$newStep = $newInversion['intervals'][$item]['steps'] + 12;

						$newInversion['intervals'][$item]['name'] = str_replace(
							$newInversion['intervals'][$item]['interval'], $newInterval, 
							$newInversion['intervals'][$item]['name']
						);
						$newInversion['intervals'][$item]['interval'] = $newInterval;
						$newInversion['intervals'][$item]['steps'] = $newStep;

						$tempInterval = $newInversion['intervals'][$item];
						unset($newInversion['intervals'][$item]);
						array_push($newInversion['intervals'], $tempInterval);
						$newInversion['intervals'] = array_values($newInversion['intervals']);

						$tempNote = $inversion['chord'][$item+1];
						unset($inversion['chord'][$item+1]);
						array_push($inversion['chord'], $tempNote);
						$newInversion['chord'] = array_values($inversion['chord']);
					}
				}

				if (!empty($newInversion)) {
					array_push($copy[$index]['inversions'], $newInversion);
					$newInversion = [];
				}			
			}
		}

		$this->array = $copy;

		return $this;
	}

	public function fixAddedIntervals()
	{
		$copy = $this->array;
		$hasTwoOrFourthOrSixth = $hasNinethOrEleventhOrThirteenth = $hasMinSecond = false;

		foreach ($this->array as $index => $result) {
			foreach ($result['inversions'] as $key => $inversion) {
				foreach ($inversion['intervals'] as $interval) {
					if (in_array($interval['interval'], [2,4,6]))
						$hasTwoOrFourthOrSixth = true;

					if (in_array($interval['interval'], [9,11,13]))
						$hasNinethOrEleventhOrThirteenth = true;

					if ($interval['name'] == 'minor 2')
						$hasMinSecond = true;
				}

				if (($hasTwoOrFourthOrSixth && $hasNinethOrEleventhOrThirteenth) || $hasMinSecond)
					unset($copy[$index]['inversions'][$key]);

				$hasTwoOrFourthOrSixth = $hasNinethOrEleventhOrThirteenth = $hasMinSecond = false;
			}
		}

		$this->array = $copy;

		return $this;
	}

	public function filters($intervals)
	{
		return $this->inversionNotValid($intervals) || 
			   $this->hasFalseSeventh($intervals) || 
			   $this->hasFalseThird($intervals) || 
			   $this->isFalseDiminished($intervals) || 
			   $this->hasFalseSixth($intervals) || 
			   $this->isFalseAugmented($intervals) || 
			   $this->missingThirdAndFifth($intervals);
	}

	public function inversionNotValid($intervals)
	{
		return in_array(null, $intervals);
	}

	public function hasFalseThird($intervals)
	{
		$hasThird = $hasDimFourth = false;

		foreach ($intervals as $interval) {
			if ($interval['interval'] == 3)
				$hasThird = true;

			if ($interval['name'] == 'diminished 4')
				$hasDimFourth = true;
		}

		return ! $hasThird && $hasDimFourth;
	}

	public function hasFalseSixth($intervals)
	{
		$hasMinThird = $hasDimFifth = $hasMajorSixth = false;

		foreach ($intervals as $interval) {
			if ($interval['name'] == 'minor 3')
				$hasMinThird = true;
			
			if ($interval['name'] == 'diminished 5')
				$hasDimFifth = true;

			if ($interval['name'] == 'major 6')
				$hasMajorSixth = true;
		}

		return $hasMinThird && $hasDimFifth && $hasMajorSixth;
	}

	public function isFalseDiminished($intervals)
	{
		$hasMajThird = false;
		$hasDimFifth = false;
		$hasDimSeventh = false;

		foreach ($intervals as $interval) {
			if ($interval['name'] == 'major 3')
				$hasMajThird = true;

			if ($interval['name'] == 'diminished 5')
				$hasDimFifth = true;

			if ($interval['name'] == 'diminished 7')
				$hasDimSeventh = true;
		}

		if (! $hasDimFifth)
			return false;

		if ($hasMajThird)
			return true;
		
		if (! $hasDimSeventh)
			return false;

		return ! $hasDimFifth && $hasDimSeventh;
	}

	public function hasFalseSeventh($intervals)
	{
		$hasDimFifth = false;
		$hasDimSeventh = false;

		foreach ($intervals as $interval) {
			if ($interval['name'] == 'diminished 5')
				$hasDimFifth = true;

			if ($interval['name'] == 'diminished 7')
				$hasDimSeventh = true;
		}

		if (! $hasDimSeventh)
			return false;

		return ! $hasDimFifth;
	}

	public function isFalseAugmented($intervals)
	{
		$hasMinThird = false;
		$hasAugFifth = false;
		$hasMinSeventh = false;
		$hasDimSeventh = false;

		foreach ($intervals as $interval) {
			if ($interval['name'] == 'minor 3')
				$hasMinThird = true;

			if ($interval['name'] == 'augmented 5')
				$hasAugFifth = true;

			if ($interval['name'] == 'minor 7')
				$hasMinSeventh = true;

			if ($interval['name'] == 'diminished 7')
				$hasDimSeventh = true;
		}

		if (! $hasAugFifth)
			return false;
		
		if ($hasMinThird)
			return true;

		return $hasMinSeventh || $hasDimSeventh;
	}

	public function missingThirdAndFifth($intervals)
	{
		$hasThird = false;
		$hasFifth = false;

		foreach ($intervals as $interval) {
			if ($interval['interval'] == 3)
				$hasThird = true;

			if ($interval['interval'] == 5)
				$hasFifth = true;
		}

		return !$hasThird && !$hasFifth;
	}

	public function get()
	{
		return $this->array;
	}
}
