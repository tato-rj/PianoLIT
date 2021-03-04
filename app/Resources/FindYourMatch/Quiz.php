<?php

namespace App\Resources\FindYourMatch;

use App\Resources\FindYourMatch\Traits\{Questions, Display};
use App\Piece;

class Quiz extends QuizFactory
{
	use Questions, Display;

	public function search()
	{
		$this->sortLevels();

		$this->findSimilar();

		$this->rankByKeywords();

		return $this->ranking->shuffle()->first() ?? Piece::freePicks()->inRandomOrder()->first();
	}
}