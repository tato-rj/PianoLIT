<?php

namespace Tests\Feature\Membership;

use Tests\AppTest;
use Tests\Traits\{BillingResources, InteractsWithStripe};
use App\User;

class ApiTest extends AppTest
{
    use BillingResources, InteractsWithStripe;

    /** @test */
    public function webapp_new_members_can_access_the_ios_app()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership();

        $this->assertTrue(auth()->user()->membership->source->isOnTrial());

        $this->assertApiStatusIs('trial');
    }

    /** @test */
    public function webapp_paying_members_can_access_the_ios_app()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership();

        $user->membership->source->update(['status' => 'active', 'renews_at' => now()->addMonth()]);

        $this->assertApiStatusIs('active');
    }

    /** @test */
    public function webapp_paused_members_can_access_the_ios_app_until_their_billing_period_ends()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership();

        $user->membership->source->update(['status' => 'paused', 'paused_at' => now()]);

        $this->assertApiStatusIs('active');
    }

    /** @test */
    public function webapp_paused_members_cannot_access_the_ios_app_after_their_billing_period_ends()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership();

        $user->membership->source->update(['status' => 'paused', 'paused_at' => now(), 'ended_at' => now()]);

        $this->assertApiStatusIs('inactive');
    }

    /** @test */
    public function webapp_canceled_members_have_access_to_the_app_until_the_end_of_the_current_billing_period()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership();

        $user->membership->source->update(['status' => 'active', 'membership_ends_at' => now()->addWeek()]);

        $this->assertTrue(auth()->user()->membership->source->isOnGracePeriod());

        $this->assertApiStatusIs('active');
    }

    /** @test */
    public function webapp_canceled_members_have_no_access_to_the_app()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership();

        $user->membership->source->update(['status' => 'canceled', 'ended_at' => now()]);

        $this->assertFalse(auth()->user()->membership->source->isOnGracePeriod());

        $this->assertApiStatusIs('inactive');
    }
}
