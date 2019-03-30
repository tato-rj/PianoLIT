<?php

namespace Tests\Traits;

use App\{Piece, Timeline};

trait FakeTimeline
{
	public function createEventsAround($date)
	{
		Piece::truncate();
		Timeline::truncate();

		create(Piece::class, ['composed_in' => $date - 4]);
		create(Piece::class, ['composed_in' => $date - 2]);
		create(Piece::class, ['composed_in' => $date + 5]);

		create(Timeline::class, ['year' => $date + 3]);
		create(Timeline::class, ['year' => $date + 10]);
		create(Timeline::class, ['year' => $date - 10]);
	}
}
