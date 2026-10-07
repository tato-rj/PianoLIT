<?php

namespace App\Resources\FindYourMatch;

use App\Resources\FindYourMatch\Traits\Display;
use App\Piece;

class Quiz extends QuizFactory
{
	use Display;

	protected $usedFallback = false;

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

	public function search($withVideoAndScore = false)
	{
		// The web tour opts in; existing public/mobile callers keep their original pool.
		$this->sortLevels();

		$this->findSimilar($withVideoAndScore);

		$this->rankByKeywords();

		$fallback = Piece::freePicks();
		if ($withVideoAndScore) $fallback->withVideoAndScore();

		$match = $this->ranking->shuffle()->first();
		$this->usedFallback = $match === null;
		return $match ?? $fallback->inRandomOrder()->first();
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
			'sharedMoods' => $shared->all(),
			'matchedTags' => $piece->tags->pluck('name')->intersect($this->tags->pluck('name'))->values()->all(),
		];
	}
}
