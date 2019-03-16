<?php

namespace Tests\Unit;

use App\{Composer, Admin, Country, Piece};
use Tests\AppTest;

class ComposerTest extends AppTest
{
	/** @test */
	public function it_belongs_to_an_admin()
	{
		$this->assertInstanceOf(Admin::class, $this->composer->creator);
	}

	/** @test */
	public function it_knows_its_nationality()
	{
		$this->assertInstanceOf(Country::class, $this->composer->country);
	}

	/** @test */
	public function it_has_many_pieces()
	{
		$this->assertInstanceOf(Piece::class, $this->composer->pieces()->first());
	}
}
