<?php

namespace Tests\Unit\Membership;

use Tests\AppTest;
use Tests\Traits\{InteractsWithStripe, BillingResources};
use App\Billing\Sources\Stripe;
use App\User;

class StripeTest extends AppTest
{
	use InteractsWithStripe, BillingResources;

    /** @test */
    public function it_knows_how_to_subscribe_a_user()
    {
        $user = create(User::class);

        Stripe::subscribe($user, $this->stripeSandbox->getCustomer());

        $this->assertInstanceOf(Stripe::class, $user->membership->source);
        
        $this->assertTrue($user->isAuthorized());
    }

    /** @test */
    public function it_knows_if_a_member_is_on_trial()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership($user);

        $this->assertTrue($user->membership->source->isOnTrial());
    }
}
