<?php

namespace Tests\Unit;

use App\{Piece, Composer, Timeline};
use Tests\Traits\FakeTimeline;
use Tests\AppTest;

class TimelineTest extends AppTest
{
	use FakeTimeline;

	/** @test */
	public function it_constructs_a_complete_timeline_that_includes_the_pieces_and_composers_databases()
	{
		$this->createEventsAround(1800);

		$piece = Piece::first();

		$this->assertCount(6, Timeline::generate($piece->id));
	}
}
