<?php

namespace Tests\Unit;

use App\{Piece, Admin};
use Tests\AppTest;

class TagTest extends AppTest
{
	/** @test */
	public function it_has_many_pieces()
	{
		$this->assertInstanceOf(Piece::class, $this->tag->pieces()->first());
	}

	/** @test */
	public function it_belongs_to_an_admin()
	{
		$this->assertInstanceOf(Admin::class, $this->tag->creator);
	}
}
