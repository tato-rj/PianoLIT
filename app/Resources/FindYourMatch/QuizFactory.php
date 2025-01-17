<?php

namespace App\Resources\FindYourMatch;

use App\{Tag, Piece};

abstract class QuizFactory
{
	protected $pieces, $tags, $levels, $similar, $ranking;

	public function getKeywords($input)
	{
		$this->pieces = collect();
		$this->levels = collect();
		$this->tags = collect();

		foreach($input as $keyword) {
			if ($this->isPiece($keyword)) {
				$piece = Piece::find($keyword);

				if ($piece)
					$this->pieces->push($piece);
			} elseif ($this->isLevel($keyword)) {
				$this->levels->push(Tag::name($keyword)->first());
			} else {
				$query = Tag::name($keyword);

				if ($query->exists())
					$this->tags->push($query->first());
			}
		}

		return $this;
	}

	public function isPiece($keyword)
	{
		return is_numeric($keyword);
	}

	public function isLevel($keyword)
	{
		$levels = Tag::levels()->pluck('name');
		
		return $levels->contains($keyword);
	}

	public function findSimilar()
	{
		$similar = collect();
		$level = $this->preferredLevel();

		foreach ($this->pieces as $piece) {
			$similar = $similar->merge($piece->similar($strict = false)->whereNotIn('composer_id', $this->exclude['composers']));
		}

		$similar = $similar->filter(function($piece, $key) use ($level) {
			$notExcluded = ! in_array($piece->id, $this->exclude['pieces']);
			$hasLevel = $piece->tags->pluck('name')->contains($level);

			return $hasLevel && $notExcluded;
		});

		$this->similar = $similar->unique();
	}

	public function rankByKeywords()
	{
		$ranking = collect();

		foreach ($this->similar as $piece) {
			$score = $piece->tags->intersect($this->tags)->count();

			$ranking->push(['score' => $score, 'piece' => $piece]);
		}

		$this->ranking = $ranking->sortByDesc('score')->pluck('piece')->take(5);
	}

	public function sortLevels()
	{
		$this->levels = $this->levels->groupBy('name')->sortDesc()->keys();
	}

	public function preferredLevel()
	{
		$levels = ['elementary', 'early beginner', 'late beginner', 'early intermediate', 'late intermediate', 'advanced'];
		$first = $this->levels->first();

		if ($this->levels->count() == 1)
			return $first;

		$second = $this->levels->get(1);

		if ($first == 'advanced')
			return 'late intermediate';

		if ($first == 'intermediate') {
			if ($second == 'advanced') {
				return 'late intermediate';
			} else {
				return 'early intermediate';
			}
		}

		if ($first == 'beginner') {
			if ($second == 'elementary') {
				return 'early beginner';
			} else {
				return 'late beginner';
			}
		}

		return $first;
	}

	// use Categories;
	
	// public function __construct()
	// {
	// 	$this->bootCategories();
	// }

	// public function getKeywords($flexible = false)
	// {
	// 	if ($flexible) {
	// 		foreach ($this->categories as $category => $class) {
	// 			if ($this->keywords->has($category)) {
	// 				$this->keywords->forget($category);
	// 				break;
	// 			}
	// 		}
	// 	}

	// 	return $this->keywords;
	// }

	// public function getResults()
	// {
	// 	$results = collect();

	// 	foreach ($this->categories as $category => $class) {
	// 		$results->push($this->$category->get());
	// 	}

	// 	return $results->flattenWithKeys();
	// }
}