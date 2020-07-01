<?php

namespace Tests\Traits;

use Stripe\Coupon;
use App\Billing\Sandbox\StripeSandbox;
use App\Billing\Factories\StripeFactory;
use App\Billing\Plan;

trait InteractsWithStripe
{
    public function postStripePurchase($route, $withCoupon = false)
    {
        return $this->post($route,
            [
                'stripeToken' => (new StripeSandbox)->token(),
                'coupon' => $withCoupon ? 'TEST-COUPON' : null
            ]
        );
    }
	public function postStripeMembership($withCoupon = false)
	{
        return $this->post(route('webapp.membership.purchase',
            [
            	'stripeToken' => (new StripeSandbox)->token(), 
            	'plan' => create(Plan::class, ['name' => 'monthly']),
            	'coupon' => $withCoupon ? 'TEST-COUPON' : null
            ]
        ));
	}

    public function assertCouponApplied()
    {
        try {
            $coupon = (new StripeFactory)->customer()->upcomingInvoice()->discount->coupon->id;

            $this->assertEquals($coupon, 'TEST-COUPON');   
        } catch (\Exception $e) {
            $this->fail('A coupon has not been applied to the customer');
        }
    }

    public function assertCouponNotApplied()
    {
        $coupon = (new StripeFactory)->customer()->upcomingInvoice()->discount;

        $this->assertNull($coupon);
    }

    public function assertChargeSucceeded($chargeId)
    {
        $charge = (new StripeFactory)->getCharge($chargeId);

        $this->assertEquals($charge->status, 'succeeded');
    }
}
