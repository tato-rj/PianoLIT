<?php

namespace Tests\Unit;

use Tests\AppTest;
use App\Admin;
use App\Infograph\{Infograph, Topic};

class InfographTest extends AppTest
{
	/** @test */
	public function it_belongs_to_an_admin()
	{
		$this->assertInstanceOf(Admin::class, $this->infograph->creator);
	}

	/** @test */
	public function it_has_many_topics()
	{
		$this->assertInstanceOf(Topic::class, $this->infograph->topics->first());
	}
}
