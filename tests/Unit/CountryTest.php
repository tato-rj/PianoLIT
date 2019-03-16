<?php

namespace Tests\Unit;

use App\{Piece, Composer};
use Tests\AppTest;

class CountryTest extends AppTest
{
	/** @test */
	public function it_has_many_composers()
	{
		$this->assertInstanceOf(Composer::class, $this->country->composers()->first());
	}

	/** @test */
	public function it_has_many_pieces()
	{
		$this->assertInstanceOf(Piece::class, $this->country->pieces()->first());
	}
}
