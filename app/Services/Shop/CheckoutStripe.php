<?php

namespace App\Services\Shop;

use Stripe\StripeClient;

/** Separate client and credentials: never mutates membership or legacy mobile billing. */
class CheckoutStripe
{
    public function call(string $resource, string $method, array $parameters = [], ?string $id = null, ?string $key = null): array
    {
        [, $secret] = app(CheckoutEnvironment::class)->credentials();
        $client = new StripeClient(['api_key' => $secret, 'stripe_version' => config('shop-checkout.stripe_version'), 'max_network_retries' => 2]);
        $service = $client;
        foreach (explode('.', $resource) as $part) $service = $service->{$part};
        $options = $key ? ['idempotency_key' => 'pianolit:shop:'.$key] : [];
        return ($id === null ? $service->{$method}($parameters, $options) : $service->{$method}($id, $parameters, $options))->toArray();
    }
}
