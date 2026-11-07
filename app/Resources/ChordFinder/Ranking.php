<?php

namespace App\Resources\ChordFinder;

class Ranking
{
	protected $chords, $strict, $keySignature;

	public function __construct(array $chords, $root, ?int $keySignature = null)
	{
		$this->chords = $chords;
		$this->strict = ! is_null($root);
		$this->keySignature = $keySignature;
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
					$ranking += 2;

				if (! in_array(3, $intervals) && in_array(5, $intervals) && (in_array(2, $intervals) || in_array(4, $intervals)))
					$ranking += 1;

				if (in_array(3, $intervals) && in_array(5, $intervals) && in_array(7, $intervals))
					$ranking += 1;

				if (in_array(3, $intervals) && in_array(5, $intervals) && in_array(6, $intervals))
					$ranking += 1;

				if (in_array(2, $intervals) || in_array(4, $intervals) || in_array(9, $intervals) || in_array(11, $intervals) || in_array(13, $intervals))
					$ranking += 1;

				if (in_array(7, $intervals) && (in_array(9, $intervals) || in_array(11, $intervals) || in_array(13, $intervals)))
					$ranking += 1;

				if (in_array(2, $intervals) && in_array(6, $intervals))
					$ranking -= 1;

				if (in_array(4, $intervals) && in_array(6, $intervals))
					$ranking -= 1;

				if (in_array(6, $intervals) && in_array(7, $intervals) || 
					in_array(13, $intervals) && in_array(7, $intervals) && (! in_array(9, $intervals) || ! in_array(11, $intervals)))
					$ranking -= 1;

				if (! in_array(3, $intervals))
					$ranking -= 1;

				$degrees = Omissions::degrees($inversion['intervals']);
				if (Omissions::seventhShell($inversion['intervals'])) {
					// One omitted third is a simpler reading than several absent
					// tones in an extended chord. Both possible qualities tie.
					$ranking = 2;
				} elseif (!isset($degrees[5])) {
					// Root/third/seventh is also a common incomplete seventh chord.
					$ranking += isset($degrees[3], $degrees[7]) ? (count($degrees) === 2 ? 2 : 1) : -1;
				}
				if (array_intersect([9, 11, 13], $intervals) && !isset($degrees[7])) {
					$ranking -= 1;
				}
				if ($this->keySignature !== null && ($inversion['label']['quality_ambiguous'] ?? false)) {
					$third = $inversion['label']['omissions'][0]['note'];
					if (KeySignature::contains($this->keySignature, $third)) {
						$ranking += 1;
						$this->chords[$index]['inversions'][$key]['label']['inferred_from_key'] = KeySignature::choices()[$this->keySignature];
					}
				}

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
		$results['strict'] = $this->strict;

		return $this->verify($results);
	}

	public function verify($results)
	{
		$mostRelevant = null;

		foreach ($results['chords'] as $index => $chord) {
			foreach ($chord['inversions'] as $key => $inversion) {
				if ($mostRelevant < $inversion['ranking'])
					$mostRelevant = $inversion['ranking'];
			}
		}

		if ($mostRelevant == $results['most_relevant'])
			return $results;

		$results['most_relevant'] = $mostRelevant;
		
		return $results;
	}
}
