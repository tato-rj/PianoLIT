<?php

namespace Tests\Unit;

use App\{Admin, Country, Pianist};
use Tests\AppTest;

class PianistTest extends AppTest
{
	/** @test */
	public function it_belongs_to_an_admin()
	{
		$this->assertInstanceOf(Admin::class, $this->pianist->creator);
	}

	/** @test */
	public function it_knows_its_nationality()
	{
		$this->assertInstanceOf(Country::class, $this->pianist->country);
	}

	/** @test */
	public function it_knows_who_was_born_or_died_between_any_given_years()
	{
		Pianist::truncate();

		create(Pianist::class, ['date_of_birth' => carbon('1802-01-01'), 'date_of_death' => carbon('1880-01-01')]);
		create(Pianist::class, ['date_of_birth' => carbon('1710-01-01'), 'date_of_death' => carbon('1798-01-01')]);

		$this->assertCount(1, Pianist::bornBetween([1780, 1820])->get());
		$this->assertCount(1, Pianist::diedBetween([1880, 1920])->get());
	}

	/** @test */
	public function it_knows_if_today_is_a_birthday()
	{
		$pianistWithoutBirthday = create(Pianist::class, ['date_of_birth' => now()->copy()->subWeek()]);
		$pianistWithBirthday = create(Pianist::class, ['date_of_birth' => now()]);

		$this->assertTrue($pianistWithBirthday->wasBornToday());
		$this->assertFalse($pianistWithoutBirthday->wasBornToday());
	}

	/** @test */
	public function it_knows_if_died_on_this_day()
	{
		$pianistDiedAnotherDay = create(Pianist::class, ['date_of_death' => now()->copy()->subWeek()]);
		$pianistDiedToday= create(Pianist::class, ['date_of_death' => now()]);

		$this->assertTrue($pianistDiedToday->hasDiedToday());
		$this->assertFalse($pianistDiedAnotherDay->hasDiedToday());
	}
}
