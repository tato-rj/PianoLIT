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

				$ranking += strlen($inversion['label']['ext']);

				if ($ranking <= 4)
					$hasRelevant = true;

				if ($ranking > 4)
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
		$best_score = 10;
		$hasRelevant = $hasIrrelevant = false;

		foreach ($this->chords as $chord) {
			if ($chord['has_relevant'])
				$hasRelevant = true;

			if ($chord['has_irrelevant'])
				$hasIrrelevant = true;

			foreach ($chord['inversions'] as $inversion) {
				if ($inversion['ranking'] < $best_score)
					$best_score = $inversion['ranking'];
			}
		}
	
		$results['chords'] = $this->chords;
		$results['has_relevant'] = $hasRelevant;
		$results['has_irrelevant'] = $hasIrrelevant;
		$results['most_relevant'] = $best_score;

		return $results;
	}
}
