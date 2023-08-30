<?php

namespace App\Resources\ChordFinder;

use App\Resources\ChordFinder\Traits\Finder;

class Validator
{
	use Finder;

	protected $array, $error;

	public function __construct(array $array)
	{
		$this->array = $array;
	}

	public function report($error = null)
	{
		if (! $this->error)
			$this->error = (new Error($error))->report();

		return $this->error;
	}

	public function check(array $array)
	{
		if (empty($array))
			abort(422, $this->error);

		$this->array = $array;
	}

	public function removeImpossible()
	{
		$copy = $this->array;

		foreach ($this->array as $index => $result) {
			foreach ($result['inversions'] as $key => $inversion) {
				if ($this->filters($inversion['intervals'], $result['inversions'][$key]['chord']))
					unset($copy[$index]['inversions'][$key]);
			}
		}

		foreach ($copy as $index => $result) {
			if (empty($result['inversions'])) {
				unset($copy[$index]);
				$this->error = $this->report();
			} else {
				$copy[$index]['inversions'] = array_values($result['inversions']);
			}
		}

		$this->check($copy);

		return $this;
	}

	public function filters($intervals, $notes)
	{
		return $this->hasRepeatedLetters($notes) || 
			   $this->hasFalseEnharmonic($notes) || 
			   $this->missingThird($intervals) || 
			   $this->hasFalseSeventh($intervals) || 
			   $this->hasFalseThird($intervals) || 
			   $this->hasFalseSixth($intervals) || 
			   $this->isFalseAugmented($intervals) || 
			   $this->missingThirdAndFifth($intervals) ||
			   $this->inversionNotValid($intervals);
	}

	public function addNinth()
	{
		$copy = $this->array;
		$newInversion = [];

		foreach ($copy as $index => $notes) {
			foreach ($notes['inversions'] as $key => $inversion) {
				if ($this->find($inversion, 7)) {
					foreach ($inversion['intervals'] as $item => $interval) {
						if (isset($interval['interval']) && $interval['interval'] == 2) {
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
				if ($this->find($inversion, 7)) {
					foreach ($inversion['intervals'] as $item => $interval) {
						if (isset($interval['interval']) && $interval['interval'] == 4) {
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
				if ($this->find($inversion, 7)) {
					foreach ($inversion['intervals'] as $item => $interval) {
						$isLast = count($inversion['intervals']) == $item +1;

						if (isset($interval['interval']) && $interval['interval'] == 6 && ! $isLast) {
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
					if (isset($interval['interval']) && in_array($interval['interval'], [2,4,6]))
						$hasTwoOrFourthOrSixth = true;

					if (isset($interval['interval']) && in_array($interval['interval'], [9,11,13]))
						$hasNinethOrEleventhOrThirteenth = true;

					if (isset($interval['name']) && $interval['name'] == 'minor 2')
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

	public function inversionNotValid($intervals)
	{
		$notValid = in_array(null, $intervals);

		if ($notValid)
			$this->error = $this->report('We couldn\'t quite figure out what this chord is. Please check those notes and try again!');

		return $notValid;
	}

	public function hasRepeatedLetters($notes)
	{
		$hasRepeatedLetters = false;

		foreach ($notes as $index => $note) {
			if (strlen($note) == 1) {
				if (in_array($note . '+', $notes) || in_array($note . '-', $notes)) {
					$hasRepeatedLetters = true;
					$this->error = $this->report('We found two ' . strtoupper($note[0]) . 's in this chord. In standard harmony, we cannot have more than one note with the same letter.');
				}
			}
		}

		return $hasRepeatedLetters;
	}

	public function hasFalseEnharmonic($notes)
	{
		$hasFalseEnharmonic = false;

		foreach ($notes as $index => $note) {
			if (array_key_exists($index+1, $notes)) {
				if ((new Interval($note, $notes[$index+1]))->isEnharmonic()) {
					$hasFalseEnharmonic = true;
					$this->error = $this->report(noteToHumans($note) . ' and ' . noteToHumans($notes[$index+1]) . ' are the same note (also called enharmonic notes), and cannot be in the same chord together.');
				}
			}
		}

		return $hasFalseEnharmonic;
	}

	public function hasFalseThird($intervals)
	{
		$falseThird = $falseFourth = false;

		foreach ($intervals as $interval) {
			if (isset($interval['name']) && $interval['name'] == 'diminished 4')
				$falseFourth = $interval['type'];

			if (isset($interval['name']) && ($interval['name'] == 'diminished 3' || $interval['name'] == 'augmented 3'))
				$falseThird = $interval['type'];
		}

		if ($falseFourth)
			$this->error = $this->report('We found the 4th to be ' . $falseFourth . ', which isn\'t a valid interval because it takes the place of the major 3rd (they are enharmonic). Did you mean this chord to have a perfect 4th or the major 3rd instead?');

		if ($falseThird)
			$this->error = $this->report('We found the 3rd to be ' . $falseThird . ', which isn\'t a valid interval. Did you mean this chord to have a major or minor 3rd?');

		return $falseThird || $falseFourth;
	}

	public function hasFalseSixth($intervals)
	{
		$hasMinThird = $hasDimFifth = $hasMajorSixth = false;

		foreach ($intervals as $interval) {
			if (isset($interval['name']) && $interval['name'] == 'minor 3')
				$hasMinThird = true;
			
			if (isset($interval['name']) && $interval['name'] == 'diminished 5')
				$hasDimFifth = true;

			if (isset($interval['name']) && $interval['name'] == 'major 6')
				$hasMajorSixth = true;
		}

		$falseSixth = $hasMinThird && $hasDimFifth && $hasMajorSixth;
		
		if ($falseSixth)
			$this->error = $this->report('The 6th in this chord should be a diminished 7th. Did you mean this to be a fully diminished chord?');

		return $falseSixth;
	}

	// public function isFalseDiminished($intervals)
	// {
	// 	$hasMajThird = $hasDimFifth = $hasDimSeventh = false;

	// 	foreach ($intervals as $interval) {
	// 		if ($interval['name'] == 'major 3')
	// 			$hasMajThird = true;

	// 		if ($interval['name'] == 'diminished 5')
	// 			$hasDimFifth = true;

	// 		if ($interval['name'] == 'diminished 7')
	// 			$hasDimSeventh = true;
	// 	}

	// 	if (! $hasDimFifth)
	// 		return false;

	// 	if ($hasMajThird)
	// 		return true;
		
	// 	if (! $hasDimSeventh)
	// 		return false;

	// 	return ! $hasDimFifth && $hasDimSeventh;
	// }

	public function hasFalseSeventh($intervals)
	{
		$hasDimFifth = $hasDimSeventh = false;

		foreach ($intervals as $interval) {
			if (isset($interval['name']) && $interval['name'] == 'diminished 5')
				$hasDimFifth = true;

			if (isset($interval['name']) && $interval['name'] == 'diminished 7')
				$hasDimSeventh = true;
		}

		$falseSeventh = ! $hasDimFifth && $hasDimSeventh;

		if ($falseSeventh)
			$this->error = $this->report('Only diminished chords can have a diminished 7th.');

		return $falseSeventh;
	}

	public function isFalseAugmented($intervals)
	{
		$hasMinThird = $hasAugFifth = $hasMinSeventh = $hasDimSeventh = false;

		foreach ($intervals as $interval) {
			if (isset($interval['name']) && $interval['name'] == 'minor 3')
				$hasMinThird = true;

			if (isset($interval['name']) && $interval['name'] == 'augmented 5')
				$hasAugFifth = true;

			if (isset($interval['name']) && $interval['name'] == 'diminished 7')
				$hasDimSeventh = true;
		}

		$falseAugmented = $hasAugFifth && $hasMinThird || $hasAugFifth && $hasDimSeventh;

		if ($hasAugFifth && $hasMinThird)
			$this->error = $this->report('An augmented chord can\'t have a minor 3rd.');

		if ($hasAugFifth && $hasDimSeventh)
			$this->error = $this->report('An augmented chord can\'t have a diminished 7th.');

		return $falseAugmented;
	}

	public function missingThird($intervals)
	{
		$hasThird = $hasSecondOrFourth = $hasTenth = false;

		foreach ($intervals as $interval) {
			if (isset($interval['interval']) && $interval['interval'] == 3)
				$hasThird = true;

			if (isset($interval['interval']) && ($interval['interval'] == 2 || $interval['interval'] == 4))
				$hasSecondOrFourth = true;

			if (isset($interval['interval']) && $interval['interval'] == 10)
				$hasTenth = true;
		}

		$missingThird = ! $hasThird && ! $hasSecondOrFourth && ! $hasTenth;

		if ($missingThird)
			$this->error = $this->report('Looks like we\'re missing the 3rd. Without it we can\'t figure out what type of chord this is (major, minor, etc).');

		return $missingThird;
	}

	public function missingThirdAndFifth($intervals)
	{
		$hasThird = $hasFifth = false;

		foreach ($intervals as $interval) {
			if (isset($interval['interval']) && in_array($interval['interval'], [3, 10]))
				$hasThird = true;

			if (isset($interval['interval']) && in_array($interval['interval'], [5, 12]))
				$hasFifth = true;
		}

		$missingThirdAndFifth = ! $hasThird && ! $hasFifth;

		if ($missingThirdAndFifth)
			$this->error = $this->report('We\'re missing both the 3rd and the 5th. Maybe this is not the correct root?');

		return $missingThirdAndFifth;
	}

	public function get()
	{
		return $this->array;
	}
}
