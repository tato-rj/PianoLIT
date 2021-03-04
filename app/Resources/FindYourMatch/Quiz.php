<?php

namespace App\Resources\FindYourMatch;

use App\Resources\FindYourMatch\Traits\Questions;
use App\{Piece, Tag};

class Quiz extends QuizFactory
{
	use Questions;

	protected $pieces, $tags, $similar, $ranking;

	public function generate()
	{
		return $this->questions;
	}

	public function search()
	{
		$this->findSimilar();

		$this->rankByKeywords();

		return $this->ranking->first();
	}

	public function getKeywords($input)
	{
		$this->pieces = collect();
		$this->tags = collect();

		foreach($input as $keyword) {
			if (is_numeric($keyword)) {
				$piece = Piece::find($keyword);

				if ($piece)
					$this->pieces->push($piece);
			} else {
				$query = Tag::name($keyword);

				if ($query->exists())
					$this->tags->push($query->first());
			}
		}

		return $this;
	}

	public function findSimilar()
	{
		$similar = collect();

		foreach ($this->pieces as $piece) {
			$similar = $similar->merge($piece->similar());
		}

		$this->similar = $similar->unique();
	}

	public function rankByKeywords()
	{
		$ranking = collect();

		foreach ($this->similar as $piece) {
			$score = $piece->tags->intersect($this->tags)->count();

			$ranking->push(['score' => $score, 'piece' => $piece]);
		}

		$this->ranking = $ranking->sortByDesc('score')->pluck('piece');
	}
}