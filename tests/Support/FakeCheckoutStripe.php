<?php

namespace Tests\Support;

use App\Services\Shop\{CheckoutStripe, CheckoutException};

class FakeCheckoutStripe extends CheckoutStripe
{
    public $calls = [], $intents = [], $lostCreateResponse = false;
    private $keys = [];

    public function call(string $resource, string $method, array $parameters = [], ?string $id = null, ?string $key = null): array
    {
        $this->calls[] = compact('resource', 'method', 'parameters', 'id', 'key');
        if ($key && isset($this->keys[$key])) return $this->keys[$key];
        if ($resource === 'paymentIntents') {
            if ($method === 'retrieve') return $this->intents[$id];
            if ($method === 'cancel') { $this->intents[$id]['status'] = 'canceled'; return $this->intents[$id]; }
            $id = 'pi_review'.(count($this->intents) + 1);
            $result = $parameters + ['id' => $id, 'client_secret' => $id.'_secret_review', 'status' => 'requires_payment_method',
                'amount_received' => 0, 'livemode' => config('shop-checkout.mode') === 'live'];
            $this->intents[$id] = $result;
        } elseif ($resource === 'coupons') $result = ['id' => $id, 'valid' => $id !== 'INVALID', 'percent_off' => $id === 'FREE' ? 100 : 50];
        elseif ($resource === 'tax.calculations') {
            $subtotal = array_sum(array_column($parameters['line_items'], 'amount')) + $parameters['shipping_cost']['amount'];
            $result = ['id' => 'taxcalc_review', 'amount_total' => $subtotal + 200, 'tax_amount_exclusive' => 200];
        } elseif ($resource === 'tax.transactions') $result = ['id' => 'tax_review'];
        else throw new \RuntimeException('Unexpected checkout test request.');
        if ($key) $this->keys[$key] = $result;
        if ($this->lostCreateResponse && $resource === 'paymentIntents' && $method === 'create') {
            $this->lostCreateResponse = false;
            throw new CheckoutException('Simulated lost response.');
        }
        return $result;
    }

    public function succeed(string $id): void
    {
        $this->intents[$id]['status'] = 'succeeded';
        $this->intents[$id]['amount_received'] = $this->intents[$id]['amount'];
    }
}
