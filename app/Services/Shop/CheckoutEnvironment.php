<?php

namespace App\Services\Shop;

use App\Shop\ShopOrder;

class CheckoutEnvironment
{
    public function mode(): string
    {
        if (app()->environment('local')) {
            if (config('shop-checkout.mode') !== 'sandbox') return 'demo';
            $key = config('shop-checkout.stripe_key') ?: config('services.stripe.us_key') ?: config('services.stripe.key');
            $secret = config('shop-checkout.stripe_secret') ?: config('services.stripe.us_secret') ?: config('services.stripe.secret');
            return is_string($key) && is_string($secret) && strpos($key, 'pk_test_') === 0 && strpos($secret, 'sk_test_') === 0 ? 'sandbox' : 'demo';
        }
        if (app()->environment('testing')) return config('shop-checkout.mode', 'demo');
        if (app()->environment('production') && config('shop-checkout.enabled') && config('shop-checkout.mode') === 'live') return 'live';
        throw new CheckoutException('Checkout is temporarily unavailable. Please try again later.');
    }

    public function available(string $kind = 'merchandise'): bool
    {
        try {
            $mode = $this->mode();
            if ($mode !== 'demo') $this->credentials();
            if ($mode === 'live' && !config('shop-checkout.stripe_webhook_secret')) return false;
            if ($mode === 'live' && $kind === 'merchandise' && (!config('shop-checkout.printful_orders_enabled') || !app(Printful::class)->enabled())) return false;
            return true;
        }
        catch (\Throwable $exception) { return false; }
    }

    public function credentials(): array
    {
        $mode = $this->mode();
        $key = config('shop-checkout.stripe_key');
        $secret = config('shop-checkout.stripe_secret');
        if ($mode === 'sandbox' && app()->environment('local', 'testing')) {
            $key = $key ?: config('services.stripe.us_key') ?: config('services.stripe.key');
            $secret = $secret ?: config('services.stripe.us_secret') ?: config('services.stripe.secret');
        }
        $prefix = $mode === 'live' ? 'live' : 'test';
        if ($mode === 'demo' || !is_string($key) || !is_string($secret)
            || strpos($key, 'pk_'.$prefix.'_') !== 0 || strpos($secret, 'sk_'.$prefix.'_') !== 0) {
            throw new CheckoutException('Secure payment is not configured for this environment.');
        }
        return [$key, $secret];
    }

    public function canFulfill(ShopOrder $order): bool
    {
        return $order->kind === 'merchandise' && $order->mode === 'live' && app()->environment('production') && $this->mode() === 'live'
            && config('shop-checkout.printful_orders_enabled') && app(Printful::class)->enabled()
            && (string) $order->printful_store_id === app(Printful::class)->storeId();
    }
}
