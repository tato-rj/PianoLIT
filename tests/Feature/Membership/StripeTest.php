<?php

namespace Tests\Feature\Membership;

use Tests\AppTest;
use Tests\Traits\{BillingResources, InteractsWithStripe};
use Stripe\Coupon;
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
        $this->assertCouponNotApplied();
    }

    /** @test */
    public function users_can_apply_a_coupon_when_subscribing_through_the_webapp()
    {
        $user = create(User::class);

        $this->signIn($user);

        $this->postStripeMembership($withCoupon = true);

        $this->assertCouponApplied();
    }

    /** @test */
    public function a_user_cannot_have_two_stripe_sources_at_the_same_time()
    {
        $this->signIn($this->stripeUser);

        $this->assertTrue($this->stripeUser->membership()->exists());

        $this->postStripeMembership($this->stripeUser)->assertRedirect(route('webapp.membership.edit'));
    }
}
