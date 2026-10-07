<?php

namespace App\Resources\FindYourMatch;

use App\Resources\FindYourMatch\Traits\Display;
use App\Piece;

class Quiz extends QuizFactory
{
	use Display;

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

		return $this->ranking->shuffle()->first() ?? $fallback->inRandomOrder()->first();
	}
}
