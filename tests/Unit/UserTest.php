<?php

namespace Tests\Unit;

use App\{Membership, Piece, Subscription};
use Tests\AppTest;

class UserTest extends AppTest
{
	/** @test */
	public function it_has_a_membership()
	{
		$this->assertInstanceOf(Membership::class, $this->user->membership);
	}

	/** @test */
	public function it_has_many_favorites()
	{
		$this->assertInstanceOf(Piece::class, $this->user->favorites->first());
	}

	/** @test */
	public function it_has_many_views()
	{
		$this->assertInstanceOf(Piece::class, $this->user->views->first());
	}

	/** @test */
	public function it_has_a_subscription()
	{
		create(Subscription::class, ['email' => $this->user->email]);

		$this->assertInstanceOf(Subscription::class, $this->user->subscription); 
	}
}
