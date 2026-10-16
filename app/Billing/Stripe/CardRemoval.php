<?php

namespace App\Billing\Stripe;

use App\Billing\{Customer, Membership};
use App\Billing\Sources\Stripe;
use App\User;
use Illuminate\Support\Facades\DB;

class CardRemoval
{
    private $billing, $gateway, $operations, $accounts;

    public function __construct(MembershipBilling $billing, Gateway $gateway, Operations $operations, Accounts $accounts)
    {
        $this->billing = $billing;
        $this->gateway = $gateway;
        $this->operations = $operations;
        $this->accounts = $accounts;
    }

    public function remove(User $user, string $id, string $reference): void
    {
        $source = $this->billing->source($user);
        $account = $this->accounts->forSource($source);
        $operation = $this->operations->begin($user, $id, 'remove_card', $account);
        // Commit the verified target before detaching it. A retry must never
        // resolve a different default card after a provider-side success.
        $this->operations->locked($user, function () use ($user, $operation, $reference) {
            $operation = $this->operations->owned($user, $operation->id, 'remove_card');
            if ($operation->state === 'complete') return;
            $source = $this->eligibleSource($user, $operation, $reference);
            if ($operation->payment_source_id) return;
            $customer = $this->gateway->call($operation->billing_account, 'customers', 'retrieve', [
                'expand' => ['invoice_settings.default_payment_method', 'default_source'],
            ], $source->stripe_id);
            if (($customer['id'] ?? null) !== $source->stripe_id || !empty($customer['deleted'])) throw new BillingException('Your saved card needs a billing review. Please contact contact@pianolit.com.');
            $target = $source->payment_method_id ?: SubscriptionState::id(data_get($customer, 'invoice_settings.default_payment_method'))
                ?: SubscriptionState::id($customer['default_source'] ?? null);
            if (!is_string($target) || !preg_match('/^(pm|card|src)_[A-Za-z0-9]+$/D', $target)) throw new BillingException('We could not identify this saved card. Please contact contact@pianolit.com.');
            $method = $this->retrieve($operation->billing_account, $source->stripe_id, $target);
            $card = strpos($target, 'pm_') === 0 || ($method['object'] ?? null) === 'source' ? ($method['card'] ?? null) : $method;
            $brand = static function ($value) {
                $value = preg_replace('/[^a-z]/', '', strtolower((string) $value));
                return ['americanexpress' => 'amex', 'dinersclub' => 'diners'][$value] ?? $value;
            };
            $owner = SubscriptionState::id($method['customer'] ?? null);
            $alreadyDetached = $target === $source->payment_method_id && strpos($target, 'pm_') === 0 && !$owner;
            if (($method['id'] ?? null) !== $target || ($owner !== $source->stripe_id && !$alreadyDetached) || !is_array($card)
                || (string) ($card['last4'] ?? '') !== (string) $source->card_last_four || $brand($card['brand'] ?? '') !== $brand($source->card_brand)
                || (strpos($target, 'card_') !== 0 && ($method['type'] ?? null) !== 'card')) {
                throw new BillingException('Your saved card has changed. Reload this page before removing it.');
            }
            $this->operations->update($operation->id, ['customer_id' => $source->stripe_id, 'subscription_id' => $source->subscription_id,
                'payment_source_id' => $target, 'card_reference' => $reference]);
        });
        $this->operations->locked($user, function () use ($user, $operation, $reference) {
            $operation = $this->operations->owned($user, $operation->id, 'remove_card');
            if ($operation->state === 'complete') return;
            $source = $this->eligibleSource($user, $operation, $reference);
            $target = $operation->payment_source_id;
            try { $method = $this->retrieve($operation->billing_account, $operation->customer_id, $target); }
            catch (\Stripe\Exception\InvalidRequestException $exception) {
                if ($exception->getStripeCode() !== 'resource_missing') throw $exception;
                $method = ['id' => $target, 'deleted' => true];
            }
            $owner = SubscriptionState::id($method['customer'] ?? null);
            if (($method['id'] ?? null) !== $target || ($owner && $owner !== $operation->customer_id)) throw new BillingException('This card does not match your account.');
            $alreadyRemoved = $this->removed($method, $target);
            if (!$owner && !$alreadyRemoved) throw new BillingException('We could not confirm card removal. Please try again.');
            if (!$alreadyRemoved) {
                $removed = strpos($target, 'pm_') === 0
                    ? $this->gateway->call($operation->billing_account, 'paymentMethods', 'detach', [], $target, $operation->id.':detach')
                    : $this->gateway->customerSource($operation->billing_account, $operation->customer_id, $target, true, $operation->id.':delete');
                if (!$this->removed($removed, $target)) {
                    throw new BillingException('We could not confirm card removal. Please try again.');
                }
            }
            $source->update(['payment_method_id' => null, 'card_brand' => null, 'card_last_four' => null]);
            $this->operations->complete($operation->id);
        });
    }

    private function eligibleSource(User $user, $operation, string $reference): Stripe
    {
        $source = $this->billing->source($user);
        $account = $this->accounts->forSource($source);
        if ($account !== $operation->billing_account || ($operation->customer_id && $operation->customer_id !== $source->stripe_id)) throw new BillingException('This card removal no longer matches your account.');
        $card = $this->billing->savedCard($user);
        if (($operation->card_reference && !hash_equals($operation->card_reference, $reference))
            || ($card ? !hash_equals($card['reference'], $reference) : !$operation->payment_source_id)) {
            throw new BillingException('Your saved card has changed. Reload this page before removing it.');
        }
        $sources = Stripe::where('stripe_id', $source->stripe_id)->get()->filter(function ($candidate) use ($account) { return $this->accounts->forSource($candidate) === $account; });
        if ($sources->count() !== 1 || Membership::where('source_type', Stripe::class)->where('source_id', $source->id)->where('user_id', '!=', $user->id)->exists()
            || Customer::where('stripe_id', $source->stripe_id)->exists()
            || DB::table('stripe_billing_profiles')->where('customer_id', $source->stripe_id)->where('billing_account', $account)->where('user_id', '!=', $user->id)->exists()) {
            throw new BillingException('Your billing customer is shared. Please contact contact@pianolit.com to remove this card.');
        }
        $live = [];
        foreach ($this->billing->subscriptions($account, $source->stripe_id) as $subscription) {
            if (SubscriptionState::id($subscription['customer'] ?? null) !== $source->stripe_id) throw new BillingException('The subscription does not match this account.');
            if (!in_array($subscription['status'] ?? null, ['canceled', 'incomplete_expired'], true)) $live[] = $subscription;
        }
        // The current membership may keep running without this card. Do not
        // affect another live subscription or guess among legacy subscriptions.
        if (count($live) > 1 || ($source->subscription_id && count($live) === 1 && ($live[0]['id'] ?? null) !== $source->subscription_id)) {
            throw new BillingException('Another subscription uses this billing customer. Please contact contact@pianolit.com to remove this card.');
        }
        return $source;
    }

    private function retrieve(string $account, string $customer, string $target): array
    {
        return strpos($target, 'pm_') === 0 ? $this->gateway->call($account, 'paymentMethods', 'retrieve', [], $target)
            : $this->gateway->customerSource($account, $customer, $target);
    }

    private function removed(array $method, string $target): bool
    {
        if (($method['id'] ?? null) !== $target) return false;
        if (strpos($target, 'pm_') === 0) return array_key_exists('customer', $method) && $method['customer'] === null;
        // Legacy Sources return a consumed Source, whereas Cards return a deleted marker.
        return !empty($method['deleted']) || (strpos($target, 'src_') === 0 && ($method['object'] ?? null) === 'source' && ($method['status'] ?? null) === 'consumed');
    }
}
