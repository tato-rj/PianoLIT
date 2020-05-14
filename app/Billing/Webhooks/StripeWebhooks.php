<?php

namespace App\Billing\Webhooks;

use App\Billing\Sources\Stripe;

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
}
