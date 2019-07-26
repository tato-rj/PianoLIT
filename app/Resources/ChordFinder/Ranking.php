<?php

namespace App\Resources\ChordFinder;

class Ranking
{
	protected $chords;

	public function __construct(array $chords)
	{
		$this->chords = $chords;
	}

	public function apply()
	{
		$hasRelevant = $hasIrrelevant = false;

		foreach ($this->chords as $index => $chord) {
			foreach ($chord['inversions'] as $key => $inversion) {
				$ranking = 0;

				foreach ($inversion['label'] as $title => $label) {
					if (strhas($title, 'shorthand') && ! empty($label))
						$ranking+=1;
				}

				if ($ranking <= 3)
					$hasRelevant = true;

				if ($ranking > 3)
					$hasIrrelevant = true;
				
				$this->chords[$index]['inversions'][$key]['ranking'] = $ranking;
				$this->chords[$index]['has_relevant'] = $hasRelevant;
				$this->chords[$index]['has_irrelevant'] = $hasIrrelevant;
			}
		}

		$results = $this->reformat();

		return $results;
	}

	public function reformat()
	{
		$results = [];
		$hasRelevant = $hasIrrelevant = false;

		foreach ($this->chords as $chord) {
			if ($chord['has_relevant'])
				$hasRelevant = true;

			if ($chord['has_irrelevant'])
				$hasIrrelevant = true;
		}
	
		$results['chords'] = $this->chords;
		$results['has_relevant'] = $hasRelevant;
		$results['has_irrelevant'] = $hasIrrelevant;

		return $results;
	}
}
