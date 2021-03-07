<?php

namespace App\Resources\FindYourMatch\Traits;

use App\{Piece, Composer, Tag};

trait Display
{
	public function showComposers()
	{
		return Composer::famous()->inRandomOrder()->take(6)->get();
	}

	public function showTags()
	{
		$tags = Tag::mood()->mostPopular()->take(12)->pluck('name');

		$tags->push($tags->first());

		return $tags;
	}

	public function showPieces()
	{
		return Piece::tour()->inRandomOrder()->get()->unique('composer_id')->take(6);
	}
}