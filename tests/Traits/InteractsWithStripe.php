<?php

namespace Tests\Traits;

use Stripe\Coupon;
use App\Billing\Sandbox\StripeSandbox;
use App\Billing\Factories\StripeFactory;
use App\Billing\Plan;

trait InteractsWithStripe
{
    public function postStripePurchase($route, $withCoupon = false, $saveCard = true)
    {
        return $this->post($route,
            [
                'stripeToken' => (new StripeSandbox)->token(),
                'coupon' => $withCoupon ? 'TEST-COUPON' : null,
                'save_card' => $saveCard
            ]
        );
    }
	public function postStripeMembership($withCoupon = false)
	{
        return $this->post(route('webapp.membership.purchase',
            [
            	'stripeToken' => (new StripeSandbox)->token(), 
            	'plan' => create(Plan::class, ['name' => 'price_1Nq5E4A3roHoLcB1lhrchbA4']),
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

    public function assertUserWasNotCharged($purchase)
    {
        $this->assertTrue($purchase->charge_id == null);
    }

    public function assertCardWasCharged($chargeId, $last4)
    {
        $charge = (new StripeFactory)->getCharge($chargeId);

        $this->assertEquals($charge->source->last4, $last4);
    }

    public function assertHasCard($stripe_id, $last4)
    {
        $cards = (new StripeFactory)->getCardsFor($stripe_id);

        if (empty($cards))
            $this->assertTrue(false);

        $this->assertEquals($cards[0]->last4, $last4);
    }

    public function assertApiStatusIs($status, $user = null)
    {
        $user = $user ?? auth()->user();

        $response = $this->post(route('api.memberships.status', ['user_id' => $user->id]));

        $this->assertEquals($response->json(), $status);
    }
}
