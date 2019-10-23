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

		$this->assertTrue($subscription->getStatusFor('newsletter_list', true));

		$subscription->deactivate('newsletter_list');

		$this->assertFalse($subscription->fresh()->getStatusFor('newsletter_list', true));

		$subscription->reactivate('newsletter_list');

		$this->assertTrue($subscription->fresh()->getStatusFor('newsletter_list', true));
	}
}
