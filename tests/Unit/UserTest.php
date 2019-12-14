<?php

namespace Tests\Unit;

use App\{Membership, Piece, Subscription, StudioPolicy};
use Tests\AppTest;
use App\Merchandise\Purchase;
use App\Infograph\Infograph;

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

	/** @test */
	public function it_has_many_studio_policies()
	{
		create(StudioPolicy::class, ['user_id' => $this->user->id]);

		$this->assertInstanceOf(StudioPolicy::class, $this->user->studioPolicies->first()); 
	}

	/** @test */
	public function it_has_many_purchases()
	{
		$this->user->purchase($this->infograph);

		$this->assertInstanceOf(Purchase::class, $this->user->purchases->first());
		$this->assertInstanceOf(Infograph::class, $this->user->purchases->first()->item);
	}
}
