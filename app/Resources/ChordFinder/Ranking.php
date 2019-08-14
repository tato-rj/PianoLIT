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
		foreach ($this->chords as $index => $chord) {
			foreach ($chord['inversions'] as $key => $inversion) {
				$ranking = 0;
				$intervals = [];

				foreach ($inversion['intervals'] as $interval) {
					array_push($intervals, $interval['interval']);
				}

				if (in_array(3, $intervals) && in_array(5, $intervals))
					$ranking += 1;

				if (in_array(6, $intervals) || in_array(7, $intervals) || in_array(9, $intervals) || in_array(11, $intervals) || in_array(13, $intervals))
					$ranking += 1;
				
				if (! in_array(3, $intervals))
					$ranking -= 1;

				$this->chords[$index]['inversions'][$key]['ranking'] = $ranking;
			}
		}

		$results = $this->reformat();

		return $results;
	}

	public function reformat()
	{
		$results = [];
		$count = 0;
		$best_score = 0;

		foreach ($this->chords as $index => $chord) {
			foreach ($chord['inversions'] as $key => $inversion) {
				$count++;

				if ($inversion['ranking'] > $best_score)
					$best_score = $inversion['ranking'];
				
				$this->chords[$index]['inversions'][$key]['id'] = str_random();
			}
		}
	
		$results['chords'] = $this->chords;
		$results['most_relevant'] = $best_score;
		$results['chords_count'] = $count;

		return $results;
	}
}
