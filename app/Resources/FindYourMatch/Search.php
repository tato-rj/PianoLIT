<?php

namespace App\Resources\FindYourMatch;

use Illuminate\Support\Collection;
use App\Resources\FindYourMatch\Traits\Categories;
use App\Piece;

class Search
{
	use Categories;

	protected $quiz, $essential, $optional, $tries;

	public function __construct(Quiz $quiz)
	{
		$this->tries = 0;
		$this->quiz = $quiz;
		$this->essential = collect();
		$this->optional = collect();
	}

	public function results($keywords = null)
	{
		if ($this->tooManyTimes())
			dd('Give up');

		// $query = Piece::whereNotNull('cover_path');
		$query = Piece::whereNotNull('name');

		$keywords = $keywords ?? $this->quiz->getKeywords();

		foreach ($keywords as $category => $keyword) {
			if ($this->quiz->isValid($category)) {
				$query = $this->quiz->$category->build($query);
			}
		}

		if (! $query->exists() || $query->first()->isFamous)
			return $this->tryAgain();

		return $query->first();
	}

	public function tryAgain()
	{
		$this->tries += 1;

		return $this->results(
			$this->quiz->getKeywords($flexible = true)
		);
	}

	public function tooManyTimes()
	{
		return $this->tries > 10;
	}
}