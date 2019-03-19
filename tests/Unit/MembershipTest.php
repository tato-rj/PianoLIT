<?php

namespace Tests\Unit;

use App\User;
use Tests\AppTest;

class MembershipTest extends AppTest
{
	/** @test */
	public function it_belongs_to_a_user()
	{
		$this->assertInstanceOf(User::class, $this->membership->user);
	}
}
