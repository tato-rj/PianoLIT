<?php

namespace Tests\Unit;

use Tests\AppTest;
use App\Admin;
use App\Infograph\{Infograph, Topic};
use App\Merchandise\Purchase;

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

	/** @test */
	public function it_has_many_downloads()
	{
		$this->user->purchase($this->infograph);
		
		$this->assertInstanceOf(Purchase::class, $this->infograph->purchases->first());
	}
}
