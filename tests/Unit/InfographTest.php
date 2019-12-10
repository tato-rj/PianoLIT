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
		Purchase::create(['user_id' => $this->user->id, 'item_id' => $this->infograph->id, 'item_type' => get_class($this->infograph)]);

		$this->assertInstanceOf(Purchase::class, $this->infograph->purchases->first());
	}
}
