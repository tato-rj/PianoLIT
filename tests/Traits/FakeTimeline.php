<?php

namespace Tests\Traits;

use App\{Piece, Timeline, Composer};

trait FakeTimeline
{
	public function createEventsAround($date)
	{
		Piece::truncate();
		Timeline::truncate();
		Composer::truncate();

		create(Piece::class, ['composed_in' => $date - 4]);
		create(Piece::class, ['composed_in' => $date - 2]);
		create(Piece::class, ['composed_in' => $date + 5]);

		create(Composer::class, ['is_famous' => true, 'date_of_birth' => carbon('2-7-' . ($date - 19))]);
		create(Composer::class, ['is_famous' => true, 'date_of_death' => carbon('10-1-' . ($date - 11))]);
		create(Composer::class, ['is_famous' => true, 'date_of_birth' => carbon('6-3-' . ($date - 15))]);
		create(Composer::class, ['is_famous' => true, 'date_of_birth' => carbon('2-7-1802')]);
		create(Composer::class, ['is_famous' => true, 'date_of_death' => carbon('10-1-1804')]);
		create(Composer::class, ['is_famous' => true, 'date_of_birth' => carbon('6-3-' . ($date + 3))]);

		create(Timeline::class, ['year' => $date + 3]);
		create(Timeline::class, ['year' => $date + 10]);
		create(Timeline::class, ['year' => $date - 10]);
		create(Timeline::class, ['year' => $date + 4]);
		create(Timeline::class, ['year' => $date - 10]);
		create(Timeline::class, ['year' => $date - 8]);
	}
}
