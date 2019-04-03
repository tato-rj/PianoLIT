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

	/** @test */
	public function it_knows_who_was_born_or_died_between_any_given_years()
	{
		Composer::truncate();

		$composer1 = create(Composer::class, ['date_of_birth' => carbon('1802-01-01'), 'date_of_death' => carbon('1880-01-01')]);
		$composer2 = create(Composer::class, ['date_of_birth' => carbon('1710-01-01'), 'date_of_death' => carbon('1798-01-01')]);

		$this->assertCount(1, Composer::bornBetween([1780, 1820])->get());
		$this->assertCount(1, Composer::diedBetween([1880, 1920])->get());
	}
}
