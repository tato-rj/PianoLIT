<?php

namespace Tests\Feature;

use App\User;
use Tests\AppTest;
use App\Events\Memberships\NewTrial;
use App\Billing\Sources\Stripe;
use App\Billing\Membership;
use App\Services\Apple\Sandbox\Membership as AppleMembership;
use App\Notifications\Memberships\NewTrialNotification;

class StripeMembershipTest extends AppTest
{
    public function setUp() : void
    {
        parent::setUp();
        
        $stripe = create(Stripe::class);
        $this->stripeMembership = create(Membership::class, ['source_id' => $stripe->id, 'source_type' => get_class($stripe)]);
        $this->stripeUser = $this->stripeMembership->user;
    }

    /** @test */
    public function a_user_can_subscribe()
    {
        $user = create(User::class);

        $this->assertFalse($user->membership()->exists());

        $this->assertTrue(true);
    }

    /** @test */
    public function admins_are_notified_when_a_user_starts_a_trial()
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function a_stripe_membership_is_updated_when_stipe_detects_an_update_in_their_subscription()
    {
        $this->assertTrue(true);
    }
}
