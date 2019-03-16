<?php

namespace Tests\Unit;

use App\{Admin, Composer, Country, Tag};
use Tests\AppTest;

class PieceTest extends AppTest
{
	/** @test */
	public function it_belonds_to_an_admin()
	{
		$this->assertInstanceOf(Admin::class, $this->piece->creator);
	}

	/** @test */
	public function it_belongs_to_a_composer()
	{
		$this->assertInstanceOf(Composer::class, $this->piece->composer);
	}

	/** @test */
	public function it_knows_its_nationality()
	{
		$this->assertInstanceOf(Country::class, $this->piece->country);
	}

	/** @test */
	public function it_has_many_tags()
	{
		$this->assertInstanceOf(Tag::class, $this->piece->tags()->first());
	}
}
