<?php

namespace App\Resources\FindYourMatch\Traits;

use App\{Piece, Composer, Tag};

trait Display
{
	public function showComposers()
	{
		return Composer::famous()->inRandomOrder()->take(12)->get();
	}

	public function showTags()
	{
		return Tag::mood()->mostPopular()->take(12)->pluck('name');
	}

	public function showPieces()
	{
		return Piece::famous()->withAudio()->withVideos()->inRandomOrder()->get()->unique('composer_id')->take(12);
	}
}