<?php

namespace App\Resources\FindYourMatch\Traits;

use App\{Piece, Composer, Tag};

trait Display
{
	public function showComposers()
	{
		return Composer::famous()->inRandomOrder()->take(8)->get();
	}

	public function showTags()
	{
		$tags = Tag::mood()->mostPopular()->take(12)->pluck('name');

		$tags->push($tags->first());

		return $tags;
	}

	public function showReadingLevels()
	{
		$levels = Tag::levels()->pluck('name');
		
		$levels->prepend($levels->first());

		$levels->reverse();

		return $levels->all();
	}

	public function showPieces()
	{
		return Piece::tour()->inRandomOrder()->get()->unique('composer_id')->take(6);
	}
}