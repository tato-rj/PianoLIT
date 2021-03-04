<?php

namespace App\Resources\FindYourMatch;

use App\Resources\FindYourMatch\Traits\{Questions, Display};

class Quiz extends QuizFactory
{
	use Questions, Display;

	public function search()
	{
		$this->sortLevels();

		$this->findSimilar();

		$this->rankByKeywords();

		return $this->ranking->first() ?? Piece::freePicks()->inRandomOrder()->first();
	}
}