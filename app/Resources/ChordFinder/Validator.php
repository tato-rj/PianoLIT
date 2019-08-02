<?php

namespace App\Resources\ChordFinder;

class Validator
{
	protected $array;

	public function __construct(array $array)
	{
		$this->array = $array;
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
					}
				}
			}
			
			if (!empty($newInversion)) {
				array_push($copy[$index]['inversions'], $newInversion);
				$newInversion = [];
			}

		}

		$this->array = $copy;

		return $this;
	}

	public function filters($intervals)
	{
		return $this->inversionNotValid($intervals) || 
			   $this->hasFalseSeventh($intervals) || 
			   $this->isFalseDiminished($intervals) || 
			   $this->hasFalseSixth($intervals) || 
			   $this->isFalseAugmented($intervals) || 
			   $this->missingThirdAndFifth($intervals);
	}

	public function inversionNotValid($intervals)
	{
		return in_array(null, $intervals);
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

		if (! $hasDimSeventh)
			return false;

		if ($hasMajThird)
			return true;

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
