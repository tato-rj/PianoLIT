<?php

namespace Tests\Unit;

use App\Subscription;
use Tests\AppTest;

class SubscriptionTest extends AppTest
{
	/** @test */
	public function it_knows_how_to_deactivate_and_reactivate_a_subscriber()
	{
		$subscription = create(Subscription::class);

		$this->assertTrue($subscription->is_active);

		$subscription->deactivate();

		$this->assertFalse($subscription->fresh()->is_active);

		$subscription->reactivate();

		$this->assertTrue($subscription->fresh()->is_active);
	}
}
