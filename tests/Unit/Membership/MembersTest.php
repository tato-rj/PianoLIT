<?php

namespace Tests\Unit\Membership;

use Tests\AppTest;
use Tests\Traits\BillingResources;
use App\Billing\Sources\{Apple, Stripe};
use App\Billing\Membership;
use App\User;

class MembersTest extends AppTest
{
	use BillingResources;
    
    /** @test */
    public function it_belongs_to_a_user()
    {
        $this->assertInstanceOf(User::class, $this->stripeMembership->user);

        $this->assertInstanceOf(User::class, $this->appleMembership->user);
    }

	/** @test */
	public function it_has_a_source()
	{
		$this->assertInstanceOf(Apple::class, $this->appleMembership->source);

		$this->assertInstanceOf(Stripe::class, $this->stripeMembership->source);
	}
	
	/** @test */
	public function it_knows_if_it_has_a_source_for_a_specific_user()
	{
		$this->assertTrue(Membership::hasSourceFor(Apple::class, $this->appleUser));

		$this->assertTrue(Membership::hasSourceFor(Stripe::class, $this->stripeUser));

		$this->assertFalse(Membership::hasSourceFor(Foo::class, $this->user));
	}
}
