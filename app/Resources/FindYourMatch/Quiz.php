<?php

namespace App\Resources\FindYourMatch;

use App\Resources\FindYourMatch\Traits\Display;
use App\Piece;

class Quiz extends QuizFactory
{
	use Display;

	protected $usedFallback = false;
	protected $nearestLevel = false;

	protected $exclude = [
		// Turk, Kabalevsly
		'composers' => [32, 50],
		'pieces' => []
	];

	public function exclude($ids)
	{
		if (is_array($ids))
			$this->exclude['pieces'] = $ids;

		return $this;
	}

	public function search($withVideoAndScore = false, $onlyFreePicks = false)
	{
		$this->nearestLevel = false;
		// The web tour opts in; existing public/mobile callers keep their original pool.
		$this->sortLevels();

		$this->findSimilar($withVideoAndScore, $onlyFreePicks);

		$this->rankByKeywords();

		$fallback = Piece::freePicks();
		if ($withVideoAndScore) $fallback->withVideoAndScore();

		$match = $this->ranking->shuffle()->first();
		$this->usedFallback = $match === null;
		if ($match === null && $onlyFreePicks) return $this->bestAvailableMatch($withVideoAndScore);
		return $match ?? $fallback->inRandomOrder()->first();
	}

	protected function levelName($name)
	{
		return preg_replace('/^(early|late) /', '', (string) $name);
	}

	protected function bestAvailableMatch($withVideoAndScore)
	{
		// Relax musical similarity and exact level together, retaining web eligibility/exclusions.
		$query = Piece::freePicks(false)->whereNotIn('pieces.id', $this->exclude['pieces'])
			->whereNotIn('composer_id', $this->exclude['composers'])->with(['tags', 'composer', 'tutorials'])->withCount([]);
		if ($withVideoAndScore) $query->withVideoAndScore();
		(new \Illuminate\Database\Eloquent\Collection($this->pieces->all()))->loadMissing('tags');
		$sharedMoods = $this->pieces->flatMap(function ($piece) {
			return $piece->tags->where('type', 'mood')->pluck('name');
		})->unique();
		$requestedTags = $this->tags->pluck('name')->unique();
		$level = $this->levelName($this->preferredLevel());
		$levels = ['elementary', 'beginner', 'intermediate', 'advanced'];
		$target = array_search($level, $levels, true);
		$ranked = $query->get()->map(function ($piece) use ($sharedMoods, $requestedTags, $level, $levels, $target) {
			$names = $piece->tags->pluck('name');
			$actualLevel = $this->levelName(optional($piece->tags->firstWhere('type', 'level'))->name);
			$actual = array_search($actualLevel, $levels, true);
			return [
				'piece' => $piece,
				'score' => ($level && $actualLevel === $level ? 100 : 0)
					+ $names->intersect($requestedTags)->count() * 20
					+ $piece->tags->where('type', 'mood')->pluck('name')->intersect($sharedMoods)->count() * 10,
				'distance' => $target !== false && $actual !== false ? abs($target - $actual) : PHP_INT_MAX,
			];
		})->filter(function ($candidate) {
			return $candidate['score'] > 0 || $candidate['distance'] !== PHP_INT_MAX;
		})->sort(function ($a, $b) {
			return ($b['score'] <=> $a['score']) ?: ($a['distance'] <=> $b['distance']);
		});
		$best = $ranked->first();
		if (!$best) return null;
		$this->nearestLevel = $best['score'] === 0;
		// Randomize only equally good matches, rather than picking an unrelated free pick.
		return $ranked->filter(function ($candidate) use ($best) {
			return $candidate['score'] === $best['score'] && $candidate['distance'] === $best['distance'];
		})->pluck('piece')->shuffle()->first();
	}

	public function matchContext(Piece $piece)
	{
		// Reuse the engine's choices and keywords; load missing choice tags in one batch.
		(new \Illuminate\Database\Eloquent\Collection($this->pieces->all()))->loadMissing('tags');
		$moods = $piece->tags->where('type', 'mood')->pluck('name');
		$shared = $this->pieces->flatMap(function ($choice) {
			return $choice->tags->where('type', 'mood')->pluck('name');
		})->intersect($moods)->unique()->values();
		return [
			'fallback' => $this->usedFallback,
			'level' => $this->preferredLevel(),
			'levelMatched' => $this->levelName(optional($piece->tags->firstWhere('type', 'level'))->name) === $this->levelName($this->preferredLevel()),
			'pieceLevel' => optional($piece->tags->firstWhere('type', 'level'))->name,
			'nearestLevel' => $this->nearestLevel,
			'sharedMoods' => $shared->all(),
			'matchedTags' => $piece->tags->pluck('name')->intersect($this->tags->pluck('name'))->values()->all(),
		];
	}
}
