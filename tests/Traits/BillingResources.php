<?php

namespace Tests\Traits;

use App\Billing\Sources\{Stripe, Apple};
use App\Billing\Membership;
use App\Services\Apple\Sandbox\Membership as AppleFakeMembership;
use App\Billing\Sandbox\StripeSandbox;

trait BillingResources
{
    public function setUp() : void
    {
        parent::setUp();

		$this->appleMembership = create(Membership::class, ['source_id' => create(Apple::class), 'source_type' => Apple::class]);
		$this->appleUser = $this->appleMembership->user;

		$this->stripeMembership = create(Membership::class, ['source_id' => create(Stripe::class), 'source_type' => Stripe::class]);
		$this->stripeUser = $this->stripeMembership->user;

        $this->stripeSandbox = new StripeSandbox;
    }

    protected function postAppleMembership($user)
    {
        $membership = new AppleFakeMembership;

        return $this->post(route('api.memberships.store'), [
            'user_id' => $user->id,
            'receipt_data' => $membership->withRequest()->receipt_data,
            'password' => $membership->withRequest()->password
        ]);
    }

    public function fakeStripeWebhook($user, $event)
    {
        $event = $this->stripeSandbox
                      ->customerId($user->membership->source->stripe_id)
                      ->getEvent($event);

        $this->post(route('webhooks.stripe', $event));
    }
}
