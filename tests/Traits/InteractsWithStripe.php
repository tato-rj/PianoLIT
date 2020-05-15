<?php

namespace Tests\Traits;

use Stripe\Coupon;
use App\Billing\Sandbox\StripeSandbox;
use App\Billing\Factories\StripeFactory;
use App\Billing\Plan;

trait InteractsWithStripe
{
	public function postStripeMembership($withCoupon = false)
	{
        $this->post(route('webapp.membership.purchase',
            [
            	'stripeToken' => (new StripeSandbox)->token(), 
            	'plan' => create(Plan::class, ['name' => 'monthly']),
            	'coupon' => $withCoupon ? 'TEST-COUPON' : null
            ]
        ));
	}

    public function couponApplied()
    {
        return (new StripeFactory)->getCoupon('TEST-COUPON')->times_redeemed > 0;
    }
}
