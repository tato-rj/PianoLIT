<?php

namespace App\Billing\Stripe;

use Stripe\StripeClient;

class Gateway
{
    public function call(string $account, string $resource, string $method, array $parameters = [], ?string $id = null, ?string $key = null): array
    {
        $client = $this->client($account);
        $options = $key ? ['idempotency_key' => 'pianolit:'.$key] : [];
        $arguments = $id === null ? [$parameters, $options] : [$id, $parameters, $options];
        return $client->{$resource}->{$method}(...$arguments)->toArray();
    }

    public function customerSource(string $account, string $customer, string $source, bool $delete = false, ?string $key = null): array
    {
        $method = $delete ? 'deleteSource' : 'retrieveSource';
        return $this->client($account)->customers->{$method}($customer, $source, [], $key ? ['idempotency_key' => 'pianolit:'.$key] : [])->toArray();
    }

    private function client(string $account): StripeClient
    {
        // Per-client credentials/version never mutate the legacy shop/mobile client.
        return new StripeClient([
            'api_key' => app(Accounts::class)->secret($account),
            'stripe_version' => config('services.stripe.membership_version'),
            'max_network_retries' => 2,
        ]);
    }
}
