<?php

namespace Tests\Unit;

use App\Membership;
use Tests\AppTest;

class UserTest extends AppTest
{
	/** @test */
	public function it_has_a_membership()
	{
		$this->assertInstanceOf(Membership::class, $this->user->membership);
	}
}
