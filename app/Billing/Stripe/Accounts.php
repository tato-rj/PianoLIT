<?php

namespace App\Billing\Stripe;

use App\Billing\Sources\Stripe;
use App\Billing\Sources\Concerns\StripeSwissCustomers;

class Accounts
{
    public function forSource(Stripe $source): string
    {
        if (in_array($source->billing_account, ['us', 'swiss'], true)) return $source->billing_account;
        return in_array($source->stripe_id, (new StripeSwissCustomers)->get(), true) ? 'swiss' : 'us';
    }

    public function key(string $account): string
    {
        return $this->setting($account, 'key');
    }

    public function secret(string $account): string
    {
        return $this->setting($account, 'secret');
    }

    private function setting(string $account, string $name): string
    {
        if (!in_array($account, ['us', 'swiss'], true)) throw new \InvalidArgumentException('Unknown Stripe billing account.');
        $value = config('services.stripe.'.$account.'_'.$name);
        if (!$value && $account === 'us') $value = config('services.stripe.'.$name);
        if (!is_string($value) || $value === '') throw new \RuntimeException('Stripe billing is temporarily unavailable.');
        return $value;
    }
}
