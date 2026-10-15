<?php

namespace App\Billing\Webhooks;

use App\Billing\Sources\Stripe;
use App\Billing\Stripe\{Accounts, WebhookProcessor};
use Illuminate\Support\Facades\DB;

/** @deprecated The signed endpoint dispatches directly to WebhookProcessor. */
class StripeWebhooks
{
    public static function whenCustomerSubscriptionDeleted($payload) { self::dispatch($payload); }
    public static function whenCustomerSubscriptionUpdated($payload) { self::dispatch($payload); }
    public static function whenCustomerSubscriptionCreated($payload) { self::dispatch($payload); }
    public static function whenCustomerSourceCreated($payload) { self::dispatch($payload); }
    public static function whenChargeSucceeded($payload) { self::dispatch($payload); }
    public static function whenInvoicePaymentSucceeded($payload) { self::dispatch($payload); }

    private static function dispatch(array $payload): void
    {
        // Compatibility for internal legacy callers. HTTP requests always use the
        // account established by signature verification in the controller.
        $customer = $payload['data']['object']['customer'] ?? null;
        $source = is_string($customer) ? Stripe::where('stripe_id', $customer)->first() : null;
        $account = $source ? app(Accounts::class)->forSource($source)
            : (DB::table('stripe_billing_profiles')->where('customer_id', $customer)->value('billing_account') ?: 'us');
        app(WebhookProcessor::class)->receive($payload, $account);
    }
}
