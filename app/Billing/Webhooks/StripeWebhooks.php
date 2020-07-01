<?php

namespace App\Billing\Webhooks;

use App\Billing\Sources\Stripe;
use App\Billing\{Payment, Membership, Customer};

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

    public static function whenCustomerSubscriptionCreated($payload)
    {
        Stripe::byCustomerId($payload['data']['object']['customer'])->updateStatus($payload['data']['object']);
    }

    public static function whenCustomerSourceCreated($payload)
    {
    	Stripe::byCustomerId($payload['data']['object']['customer'])->updateCard($payload['data']['object']);
    }

    public static function whenChargeSucceeded($payload)
    {
        Payment::create([
            'user_id' => self::handleChargeCustomer($payload),
            'charge_id' => $payload['data']['object']['id'],
            'amount' => $payload['data']['object']['amount'],
            'refund' => $payload['data']['object']['amount_refunded']
        ]);
    }

    public static function handleChargeCustomer($payload)
    {
        if (Stripe::customerExists($payload['data']['object']['customer'])) {
            $stripe = Stripe::byCustomerId($payload['data']['object']['customer']);
            $membership = Membership::bySource($stripe);
            return $membership->user()->exists() ? $membership->user->id : null;
        } else {
            $customer = Customer::byCustomerId($payload['data']['object']['customer']);
            return $customer->user()->exists() ? $customer->user->id : null;
        }
    }
}
