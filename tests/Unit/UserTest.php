<?php

namespace Tests\Unit;

use App\{Membership, Piece, Subscription, StudioPolicy, TutorialRequest};
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
	public function it_has_many_tutorial_requests()
	{
		create(TutorialRequest::class, ['user_id' => $this->user->id]);

		$this->assertInstanceOf(TutorialRequest::class, $this->user->tutorialRequests->first());		 
	}

	/** @test */
	public function it_knows_if_it_has_pending_tutorial_requests()
	{
		$this->assertEmpty($this->user->pendingTutorialRequests()->get());

		create(TutorialRequest::class, ['user_id' => $this->user->id]);
		 
		$this->assertNotEmpty($this->user->fresh()->pendingTutorialRequests()->get());
	}

	/** @test */
	public function it_has_many_purchases()
	{
		$this->user->purchase($this->infograph);

		$this->assertInstanceOf(Purchase::class, $this->user->purchases->first());
		$this->assertInstanceOf(Infograph::class, $this->user->purchases->first()->item);
	}

	/** @test */
	public function it_knows_its_logs_from_the_web()
	{
		$this->signIn($this->user);

		$this->assertEquals(count($this->user->log()->web), 0);

		$this->get(route('home'));

		$this->assertEquals(count($this->user->log()->web), 1);
	}

	/** @test */
	public function it_knows_its_logs_from_the_app()
	{
		$this->assertEquals(count($this->user->log()->app), 0);

		$this->get(route('api.search', ['user_id' => $this->user->id, 'search' => 'foo bar']));

		$this->assertEquals(count($this->user->log()->app), 1);
	}

	/** @test */
	public function it_knows_when_was_its_last_activity()
	{
		$this->signIn($this->user);

		$this->assertNull($this->user->lastActive());

		$this->get(route('home'));

		$this->assertInstanceOf(\Carbon\Carbon::class, $this->user->lastActive());
	}
}
