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

    /** @test */
    public function it_knows_when_a_trial_converts_into_a_active_membership()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership($user);

        $this->fakeStripeWebhook($user, 'subscriptionRenewed');

        $this->assertTrue($user->membership->source->isActive());
    }

    /** @test */
    public function it_knows_when_a_trial_does_not_convert_into_a_membership()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership($user);

        $this->fakeStripeWebhook($user, 'subscriptionDidNotRenew');

        $this->assertFalse($user->fresh()->membership->source->isActive());
    }

    /** @test */
    public function it_knows_when_a_trial_is_set_to_not_convert_into_a_membership()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership($user);

        $this->assertTrue($user->membership->source->willRenew());

        $this->fakeStripeWebhook($user, 'subscriptionWillNotRenew');

        $this->assertTrue($user->membership->source->isActive());

        $this->assertFalse($user->fresh()->membership->source->willRenew());
    }

    /** @test */
    public function it_knows_when_a_user_pauses_or_resumes_their_membership()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership($user);

        $this->assertTrue($user->membership->source->isActive());
        $this->assertFalse($user->membership->source->isPaused());

        $this->fakeStripeWebhook($user, 'subscriptionPaused');

        $this->assertFalse($user->fresh()->membership->source->isActive());
        $this->assertTrue($user->fresh()->membership->source->isPaused());

        $this->fakeStripeWebhook($user, 'subscriptionResumed');

        $this->assertTrue($user->fresh()->membership->source->isActive());
        $this->assertFalse($user->fresh()->membership->source->isPaused());
    }

    // /** @test */
    // public function it_knows_its_status()
    // {
    //     $this->stripeUser->membership->source->update(['renews_at' => now()->copy()->addDays(4)]);

    //     $this->assertTrue($this->stripeUser->isOnTrial);

    //     $this->assertEquals('active', $this->stripeUser->getStatus());

    //     $this->stripeUser->membership->source->update(['renews_at' => now()->copy()->addDays(20)]);

    //     $this->assertFalse($this->stripeUser->isOnTrial);

    //     $this->assertEquals('active', $this->stripeUser->getStatus());

    //     $this->stripeUser->membership->source->update(['renews_at' => now()->copy()->subDay(), 'status']);

    //     $this->assertFalse($this->stripeUser->isOnTrial);
        
    //     $this->assertEquals('inactive', $this->stripeUser->getStatus());
    // }
}
