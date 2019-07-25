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

	public function filters($intervals)
	{
		return $this->inversionNotValid($intervals) || 
			   $this->isFalseDiminished($intervals) || 
			   $this->isFalseAugmented($intervals) || 
			   $this->missingThirdAndFifth($intervals);
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

		if ($hasMajThird)
			return true;

		if (! $hasDimSeventh)
			return false;

		return ! $hasDimFifth && $hasDimSeventh;
	}

	public function isFalseAugmented($intervals)
	{
		$hasAugFifth = false;
		$hasMinSeventh = false;
		$hasDimSeventh = false;

		foreach ($intervals as $interval) {
			if ($interval['name'] == 'augmented 5')
				$hasAugFifth = true;

			if ($interval['name'] == 'minor 7')
				$hasMinSeventh = true;

			if ($interval['name'] == 'diminished 7')
				$hasDimSeventh = true;
		}

		if (! $hasAugFifth)
			return false;

		return $hasMinSeventh || $hasDimSeventh;
	}

	public function inversionNotValid($intervals)
	{
		return in_array(null, $intervals);
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
