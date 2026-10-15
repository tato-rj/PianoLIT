<?php

namespace App\Billing\Stripe;

use Stripe\StripeClient;

class Gateway
{
    public function call(string $account, string $resource, string $method, array $parameters = [], ?string $id = null, ?string $key = null): array
    {
        // Per-client credentials/version never mutate the legacy shop/mobile client.
        $client = new StripeClient([
            'api_key' => app(Accounts::class)->secret($account),
            'stripe_version' => config('services.stripe.membership_version'),
            'max_network_retries' => 2,
        ]);
        $options = $key ? ['idempotency_key' => 'pianolit:'.$key] : [];
        $arguments = $id === null ? [$parameters, $options] : [$id, $parameters, $options];
        return $client->{$resource}->{$method}(...$arguments)->toArray();
    }
}
