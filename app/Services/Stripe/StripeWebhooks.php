<?php

namespace App\Services\Stripe;

use App\Billing\Sources\Stripe;

class StripeWebhooks
{
    public static function whenCustomerSubscriptionDeleted($payload)
    {
    	Stripe::byCustomerId($payload['data']['object']['customer'])->cancel($payload['data']['object']);
    }

    public static function whenCustomerSubscriptionUpdated($payload)
    {
    	Stripe::byCustomerId($payload['data']['object']['customer'])->updateStatus($payload['data']['object']);
    }
}
