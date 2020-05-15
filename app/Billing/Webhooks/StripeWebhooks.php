<?php

namespace App\Billing\Webhooks;

use App\Billing\Sources\Stripe;
use App\Billing\{Payment, Membership};

class StripeWebhooks
{
    public static function whenCustomerSubscriptionDeleted($payload)
    {
    	Stripe::byCustomerId($payload['data']['object']['customer'])->updateStatus($payload['data']['object']);
    }

    public static function whenCustomerSubscriptionUpdated($payload)
    {
    	Stripe::byCustomerId($payload['data']['object']['customer'])->updateStatus($payload['data']['object']);
    }

    public static function whenCustomerSourceCreated($payload)
    {
    	Stripe::byCustomerId($payload['data']['object']['customer'])->updateCard($payload['data']['object']);
    }

    public static function whenChargeSucceeded($payload)
    {
        $stripe = Stripe::byCustomerId($payload['data']['object']['customer']);
        $membership = Membership::bySource($stripe);

        Payment::create([
            'user_id' => $membership->user()->exists() ? $membership->user->id : null,
            'charge_id' => $payload['data']['object']['id'],
            'amount' => $payload['data']['object']['amount'],
            'refund' => $payload['data']['object']['amount_refunded']
        ]);
    }
}
