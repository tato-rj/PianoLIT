<?php

namespace Tests\Support;

use App\Billing\Stripe\Gateway;

class FakeStripeGateway extends Gateway
{
    public $calls = [], $customers = [], $intents = [], $subscriptions = [], $schedules = [], $invoices = [], $prices = [], $coupons = [];
    public $nextSubscription, $failure, $intercept;
    private $keys = [];

    public function call(string $account, string $resource, string $method, array $parameters = [], ?string $id = null, ?string $key = null): array
    {
        $this->calls[] = compact('account', 'resource', 'method', 'parameters', 'id', 'key');
        if ($this->intercept) ($this->intercept)(end($this->calls));
        if ($this->failure && $this->failure[0] === $resource.'.'.$method) {
            $error = $this->failure[1]; $this->failure = null; throw $error;
        }
        if ($key && isset($this->keys[$account.':'.$key])) return $this->keys[$account.':'.$key];
        $result = $this->respond($account, $resource, $method, $parameters, $id);
        if ($key) $this->keys[$account.':'.$key] = $result;
        return $result;
    }

    private function respond($account, $resource, $method, $p, $id): array
    {
        if ($resource === 'prices') return $this->prices[$id] ?? ['id' => $id, 'active' => true, 'currency' => 'usd', 'unit_amount' => $id === 'yearly' ? 8999 : 799, 'product' => 'prod_review', 'recurring' => ['interval' => $id === 'yearly' ? 'year' : 'month', 'interval_count' => 1]];
        if ($resource === 'coupons') return $this->coupons[$id] ?? ['id' => $id, 'valid' => true, 'percent_off' => 50];
        if ($resource === 'customers') {
            if ($method === 'create') {
                $id = $this->nextSubscription['customer'] ?? 'cus_review';
                return $this->customers[$id] = ['id' => $id, 'invoice_settings' => ['default_payment_method' => null], 'default_source' => ['brand' => 'visa', 'last4' => '4242']];
            }
            return $this->customers[$id] ?? ['id' => $id, 'invoice_settings' => ['default_payment_method' => null], 'default_source' => ['brand' => 'visa', 'last4' => '4242']];
        }
        if ($resource === 'setupIntents') {
            if ($method === 'create') {
                $id = 'seti_'.str_replace('-', '', $p['metadata']['pianolit_operation']);
                return $this->intents[$id] = $p + ['id' => $id, 'client_secret' => $id.'_secret_review', 'status' => 'requires_payment_method'];
            }
            if (!isset($this->intents[$id])) throw new \RuntimeException('Unknown test setup intent');
            return $this->intents[$id];
        }
        if ($resource === 'paymentMethods') return ['id' => $id, 'customer' => $this->nextSubscription['customer'] ?? 'cus_review', 'type' => 'card', 'card' => ['brand' => 'visa', 'last4' => '4444']];
        if ($resource === 'subscriptions') {
            if ($method === 'all') {
                $rows = array_values(array_filter($this->subscriptions, function ($s) use ($p) { return $s['customer'] === $p['customer']; }));
                return ['data' => $rows, 'has_more' => false];
            }
            if ($method === 'create') {
                $id = $this->nextSubscription['id'] ?? 'sub_review';
                $price = $p['items'][0]['price'];
                $subscription = ['id' => $id, 'customer' => $p['customer'], 'status' => !empty($p['trial_period_days']) ? 'trialing' : 'incomplete',
                    'items' => ['data' => [['id' => 'si_review', 'price' => ['id' => $price], 'quantity' => 1, 'current_period_start' => now()->timestamp, 'current_period_end' => now()->addMonth()->timestamp]]],
                    'created' => now()->timestamp, 'trial_start' => now()->timestamp, 'trial_end' => !empty($p['trial_period_days']) ? now()->addDays($p['trial_period_days'])->timestamp : null,
                    'cancel_at_period_end' => false, 'cancel_at' => null, 'canceled_at' => null, 'ended_at' => null, 'pause_collection' => null,
                    'metadata' => $p['metadata'], 'default_payment_method' => $p['default_payment_method'], 'schedule' => null,
                    'latest_invoice' => ['id' => 'in_review', 'status' => !empty($p['trial_period_days']) ? 'paid' : 'open', 'confirmation_secret' => ['client_secret' => 'pi_review_secret_test'], 'hosted_invoice_url' => 'https://invoice.stripe.com/i/review']];
                if ($this->nextSubscription) $subscription = array_replace($subscription, $this->nextSubscription);
                return $this->subscriptions[$id] = $subscription;
            }
            if (!isset($this->subscriptions[$id])) throw new \RuntimeException('Unknown test subscription '.$id);
            if ($method === 'cancel') {
                $this->subscriptions[$id]['status'] = 'canceled';
                $this->subscriptions[$id]['ended_at'] = now()->timestamp;
                $this->subscriptions[$id]['canceled_at'] = now()->timestamp;
            }
            if ($method === 'update') {
                unset($p['expand']);
                if (isset($p['pause_collection']) && $p['pause_collection'] === '') $p['pause_collection'] = null;
                if (array_key_exists('cancel_at_period_end', $p)) {
                    $p['cancel_at'] = $p['cancel_at_period_end'] ? \App\Billing\Stripe\SubscriptionState::periodEnd($this->subscriptions[$id]) : null;
                    $p['canceled_at'] = $p['cancel_at_period_end'] ? now()->timestamp : null;
                }
                $this->subscriptions[$id] = array_replace($this->subscriptions[$id], $p);
            }
            return $this->subscriptions[$id];
        }
        if ($resource === 'subscriptionSchedules') {
            if ($method === 'create') {
                $id = 'sub_sched_review'; $sub = $this->subscriptions[$p['from_subscription']];
                $this->subscriptions[$sub['id']]['schedule'] = $id;
                return $this->schedules[$id] = ['id' => $id, 'subscription' => $sub['id'], 'metadata' => $p['metadata'] ?? [],
                    'current_phase' => ['start_date' => now()->timestamp],
                    'phases' => [['start_date' => now()->timestamp, 'items' => [['price' => $sub['items']['data'][0]['price']['id'], 'quantity' => 1]], 'discounts' => [['discount' => 'di_existing']]]]];
            }
            if ($method === 'update') $this->schedules[$id] = array_replace($this->schedules[$id], $p);
            if ($method === 'release') $this->subscriptions[$this->schedules[$id]['subscription']]['schedule'] = null;
            return $this->schedules[$id];
        }
        if ($resource === 'invoices') {
            if ($method === 'createPreview') return ['currency' => 'usd', 'amount_due' => 799, 'period_end' => now()->addMonth()->timestamp];
            $items = array_values(array_filter($this->invoices, function ($item) use ($p) {
                return (!isset($p['status']) || $item['status'] === $p['status']) && (!isset($item['customer']) || $item['customer'] === $p['customer']);
            }));
            return ['data' => $items, 'has_more' => false];
        }
        if ($resource === 'charges') return ['id' => $id, 'customer' => 'cus_review', 'status' => 'succeeded', 'amount' => 799, 'amount_refunded' => 0, 'currency' => 'usd'];
        throw new \RuntimeException('Unexpected Stripe test request: '.$resource.'.'.$method);
    }

    public function confirm($id): void
    {
        $this->intents[$id]['status'] = 'succeeded';
        $this->intents[$id]['payment_method'] = 'pm_review';
    }

    public function callsTo($resource, $method): array
    {
        return array_values(array_filter($this->calls, function ($call) use ($resource, $method) { return $call['resource'] === $resource && $call['method'] === $method; }));
    }
}
