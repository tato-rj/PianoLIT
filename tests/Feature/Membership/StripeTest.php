<?php

namespace Tests\Feature\Membership;

use Tests\AppTest;
use Tests\Traits\{BillingResources, InteractsWithStripe};
use App\User;

class StripeTest extends AppTest
{
    use BillingResources, InteractsWithStripe;

    /** @test */
    public function a_user_can_subscribe_through_the_webapp()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->assertFalse($user->membership()->exists());

        $this->postStripeMembership();

        $this->assertTrue($user->membership()->exists());
    }

    /** @test */
    public function it_cannot_have_two_stripe_sources_at_the_same_time()
    {
        $this->signIn($this->stripeUser);

        $this->expectException('Illuminate\Auth\Access\AuthorizationException');

        $this->assertTrue($this->stripeUser->membership()->exists());

        $this->postStripeMembership($this->stripeUser);
    }
    
    /** @test */
    public function a_users_account_is_deactivated_if_a_subscription_is_deleted_on_stripe()
    {        
        $this->assertFalse($this->stripeUser->membership->source->isEnded());

        $this->fakeStripeWebhook($this->stripeUser, 'subscriptionDeleted');

        $this->assertTrue($this->stripeUser->fresh()->membership->source->isEnded());
    }
}
