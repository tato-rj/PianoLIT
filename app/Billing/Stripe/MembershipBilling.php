<?php

namespace App\Billing\Stripe;

use App\User;
use App\Billing\{Membership, Plan};
use App\Billing\Sources\Stripe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MembershipBilling
{
    private $gateway, $accounts, $operations;

    public function __construct(Gateway $gateway, Accounts $accounts, Operations $operations)
    {
        $this->gateway = $gateway;
        $this->accounts = $accounts;
        $this->operations = $operations;
    }

    public function source(User $user, bool $required = true): ?Stripe
    {
        $records = $user->membership()->get();
        if ($records->count() > 1) throw new BillingException('Your account has multiple billing records. Please contact contact@pianolit.com before making billing changes.');
        $record = $records->first();
        if ($record && $record->source_type !== Stripe::class) throw new BillingException('This account uses App Store billing. Manage it through Apple, or contact contact@pianolit.com to switch billing providers.');
        if ($record && !$record->source) throw new BillingException('Your billing record needs review. Please contact contact@pianolit.com.');
        if (!$record && $required) throw new BillingException('You do not have a Stripe membership to manage.');
        return $record ? $record->source : null;
    }

    public function account(User $user): string
    {
        $source = $this->source($user, false);
        if ($source) return $this->accounts->forSource($source);
        return DB::table('stripe_billing_profiles')->where('user_id', $user->id)->value('billing_account') ?: 'us';
    }

    public function publicKey(User $user): string
    {
        return $this->accounts->key($this->account($user));
    }

    public function trialDays(User $user, Plan $plan): int
    {
        // Existing customers do not receive another introductory trial.
        return $user->membership()->exists() ? 0 : max(0, (int) $plan->trial_period_days);
    }

    public function activePurchase(User $user)
    {
        return DB::table('stripe_billing_operations')->where('active_key', 'purchase:'.$user->id)->first();
    }

    private function profile(User $user, string $account)
    {
        // Commit the provisioning identity before any network request.
        return $this->operations->locked($user, function () use ($user, $account) {
            $source = $this->source($user, false);
            $profile = DB::table('stripe_billing_profiles')->where('user_id', $user->id)->first();
            if ($profile && ($profile->billing_account !== $account || ($source && $profile->customer_id && $profile->customer_id !== $source->stripe_id))) {
                throw new BillingException('Your Stripe customer mapping needs review. Please contact contact@pianolit.com.');
            }
            if (!$profile) {
                DB::table('stripe_billing_profiles')->insert([
                    'id' => (string) Str::uuid(), 'user_id' => $user->id, 'billing_account' => $account,
                    'customer_id' => $source ? $source->stripe_id : null, 'created_at' => now(), 'updated_at' => now(),
                ]);
            } elseif ($source && !$profile->customer_id) {
                DB::table('stripe_billing_profiles')->where('id', $profile->id)->update(['customer_id' => $source->stripe_id, 'updated_at' => now()]);
            }
            return DB::table('stripe_billing_profiles')->where('user_id', $user->id)->first();
        });
    }

    private function customer(User $user, $profile): string
    {
        $current = DB::table('stripe_billing_profiles')->where('id', $profile->id)->first();
        if ($current->customer_id) return $current->customer_id;
        if (carbon($current->created_at)->lt(now()->subHours(23))) throw new BillingException('This checkout needs a billing review before it can be retried. Please contact contact@pianolit.com.');
        $customer = $this->gateway->call($profile->billing_account, 'customers', 'create', [
            'name' => $user->full_name, 'email' => $user->email,
            'metadata' => ['pianolit_profile' => $profile->id],
        ], null, $profile->id.':customer');
        DB::table('stripe_billing_profiles')->where('id', $profile->id)->update(['customer_id' => $customer['id'], 'updated_at' => now()]);
        return $customer['id'];
    }

    public function prepare(User $user, string $id, string $kind, ?Plan $plan = null, ?string $coupon = null): array
    {
        $account = $this->account($user);
        $coupon = $coupon ? strtoupper(trim($coupon)) : null;
        if ($kind === 'card') $this->source($user);
        $operation = $this->operations->begin($user, $id, $kind, $account, $plan ? $plan->name : null, $coupon);
        $profile = $this->profile($user, $account);
        return $this->operations->locked($user, function () use ($user, $operation, $profile, $plan) {
            $operation = $this->operations->owned($user, $operation->id, $operation->kind);
            if ($operation->subscription_id && $operation->kind === 'purchase') {
                return $this->paymentResult($this->subscription($user), $operation);
            }
            if ($operation->state === 'complete') return ['operation_id' => $operation->id, 'complete' => true];
            if ($operation->kind === 'purchase') {
                $this->availableToPurchase($user);
                $price = $this->price($plan, $operation->billing_account);
                if ($operation->coupon) $this->coupon($operation->billing_account, $operation->coupon, $price);
            }
            $customer = $this->customer($user, $profile);
            $this->operations->update($operation->id, ['customer_id' => $customer]);
            $intent = $operation->setup_intent_id
                ? $this->gateway->call($operation->billing_account, 'setupIntents', 'retrieve', [], $operation->setup_intent_id)
                : $this->gateway->call($operation->billing_account, 'setupIntents', 'create', [
                    'customer' => $customer, 'payment_method_types' => ['card'], 'usage' => 'off_session',
                    'metadata' => ['pianolit_operation' => $operation->id],
                ], null, $operation->id.':setup');
            $this->operations->update($operation->id, ['setup_intent_id' => $intent['id']]);
            return ['operation_id' => $operation->id, 'setup_secret' => $intent['client_secret'],
                'setup_intent' => $intent['id'], 'setup_complete' => ($intent['status'] ?? null) === 'succeeded'];
        });
    }

    private function availableToPurchase(User $user): void
    {
        $source = $this->source($user, false);
        if ($source && !$source->ended_at && !in_array($source->status, ['canceled', 'incomplete_expired'], true)) {
            throw new BillingException('You already have a membership. Open My Membership to manage or finish your existing subscription.');
        }
    }

    public function price(Plan $plan, string $account): array
    {
        $price = $this->gateway->call($account, 'prices', 'retrieve', [], $this->priceId($plan, $account));
        if (empty($price['active']) || ($price['currency'] ?? null) !== 'usd' || (int) ($price['unit_amount'] ?? -1) !== (int) $plan->price
            || data_get($price, 'recurring.interval') !== $plan->interval || (int) data_get($price, 'recurring.interval_count', 1) !== 1) {
            throw new BillingException('This plan is temporarily unavailable while its price is being updated. You have not been subscribed.');
        }
        return $price;
    }

    public function priceId(Plan $plan, string $account): string
    {
        return config('memberships.stripe_prices.'.$account.'.'.$plan->name) ?: $plan->name;
    }

    public function coupon(string $account, string $code, ?array $price = null): array
    {
        $coupon = $this->gateway->call($account, 'coupons', 'retrieve', [], strtoupper(trim($code)));
        if (empty($coupon['valid'])) throw new BillingException('This coupon is no longer valid.');
        if ($price) {
            $products = data_get($coupon, 'applies_to.products', []);
            if (($products && !in_array(SubscriptionState::id($price['product'] ?? null), $products, true))
                || (!empty($coupon['amount_off']) && ($coupon['currency'] ?? null) !== 'usd')) throw new BillingException('This coupon does not apply to this plan.');
        }
        return $coupon;
    }

    private function verifiedSetup($operation, string $id): array
    {
        if ($operation->setup_intent_id !== $id) throw new BillingException('The card confirmation does not match this checkout.');
        $intent = $this->gateway->call($operation->billing_account, 'setupIntents', 'retrieve', [], $id);
        if (($intent['status'] ?? null) !== 'succeeded' || SubscriptionState::id($intent['customer'] ?? null) !== $operation->customer_id
            || data_get($intent, 'metadata.pianolit_operation') !== $operation->id || !SubscriptionState::id($intent['payment_method'] ?? null)) {
            throw new BillingException('Please finish confirming your card before continuing.');
        }
        return $intent;
    }

    public function purchase(User $user, Plan $plan, string $id, string $setup): array
    {
        return $this->operations->locked($user, function () use ($user, $plan, $id, $setup) {
            $operation = $this->operations->owned($user, $id, 'purchase');
            if ($operation->plan !== $plan->name) throw new BillingException('The selected plan does not match this checkout.');
            if ($operation->subscription_id) return $this->paymentResult($this->subscription($user), $operation);
            $this->availableToPurchase($user);
            $intent = $this->verifiedSetup($operation, $setup);
            $price = $this->price($plan, $operation->billing_account);
            $existing = $this->subscriptions($operation->billing_account, $operation->customer_id);
            $subscription = null;
            foreach ($existing as $candidate) {
                if (data_get($candidate, 'metadata.pianolit_operation') === $id) $subscription = $candidate;
                elseif (!in_array($candidate['status'], ['canceled', 'incomplete_expired'], true)) throw new BillingException('A subscription already exists. Please open My Membership or contact contact@pianolit.com.');
            }
            if (!$subscription) {
                if (carbon($operation->created_at)->lt(now()->subHours(23))) throw new BillingException('This checkout needs a billing review before it can be retried. Please contact contact@pianolit.com.');
                $parameters = [
                    'customer' => $operation->customer_id, 'items' => [['price' => $price['id']]],
                    'default_payment_method' => SubscriptionState::id($intent['payment_method']),
                    'payment_behavior' => 'default_incomplete',
                    'payment_settings' => ['save_default_payment_method' => 'on_subscription', 'payment_method_types' => ['card']],
                    'metadata' => ['pianolit_operation' => $id],
                    'expand' => ['latest_invoice.confirmation_secret', 'pending_setup_intent'],
                ];
                if ($days = $this->trialDays($user, $plan)) $parameters['trial_period_days'] = $days;
                if ($operation->coupon) {
                    $coupon = $this->coupon($operation->billing_account, $operation->coupon, $price);
                    $parameters['discounts'] = [['coupon' => $coupon['id']]];
                }
                $subscription = $this->gateway->call($operation->billing_account, 'subscriptions', 'create', $parameters, null, $id.':subscription');
            }
            // An idempotent response may be an older snapshot if a prior request
            // succeeded remotely but failed locally. Read the current object.
            $subscription = $this->gateway->call($operation->billing_account, 'subscriptions', 'retrieve', [
                'expand' => ['latest_invoice.confirmation_secret', 'pending_setup_intent'],
            ], $subscription['id']);
            $this->save($user, $operation->billing_account, $subscription, $plan->name);
            $this->operations->update($id, ['subscription_id' => $subscription['id'], 'state' => 'confirming']);
            return $this->paymentResult($subscription, $operation);
        });
    }

    public function subscriptions(string $account, string $customer): array
    {
        $result = []; $after = null;
        do {
            $parameters = ['customer' => $customer, 'status' => 'all', 'limit' => 100];
            if ($after) $parameters['starting_after'] = $after;
            $page = $this->gateway->call($account, 'subscriptions', 'all', $parameters);
            $result = array_merge($result, $page['data']);
            $after = count($page['data']) ? end($page['data'])['id'] : null;
            if (count($result) > 1000 || (!empty($page['has_more']) && !$after)) throw new BillingException('This account needs a billing review. Please contact contact@pianolit.com.');
        } while (!empty($page['has_more']));
        return $result;
    }

    public function subscription(User $user): array
    {
        $source = $this->source($user);
        $account = $this->accounts->forSource($source);
        $id = $source->subscription_id;
        if (!$id) {
            $all = $this->subscriptions($account, $source->stripe_id);
            $live = array_values(array_filter($all, function ($item) { return !in_array($item['status'], ['canceled', 'incomplete_expired'], true); }));
            $candidates = count($live) ? $live : $all;
            if (count($candidates) !== 1) throw new BillingException('Your subscription needs a billing review. Please contact contact@pianolit.com.');
            $id = $candidates[0]['id'];
        }
        $subscription = $this->gateway->call($account, 'subscriptions', 'retrieve', [
            'expand' => ['latest_invoice.confirmation_secret', 'pending_setup_intent'],
        ], $id);
        if (($subscription['id'] ?? null) !== $id || SubscriptionState::id($subscription['customer'] ?? null) !== $source->stripe_id) throw new BillingException('The subscription does not match this account.');
        $this->save($user, $account, $subscription);
        return $subscription;
    }

    public function save(User $user, string $account, array $subscription, ?string $plan = null): Stripe
    {
        $source = $this->source($user, false);
        $customer = SubscriptionState::id($subscription['customer'] ?? null);
        if (!$customer || ($source && $source->stripe_id !== $customer)) throw new BillingException('The subscription does not match this account.');
        if (!$source && !DB::table('stripe_billing_profiles')->where('user_id', $user->id)->where('billing_account', $account)->where('customer_id', $customer)->exists()) {
            throw new BillingException('The subscription does not match this checkout.');
        }
        $attributes = SubscriptionState::attributes($subscription);
        if (!$plan) {
            foreach (Plan::all() as $candidate) {
                if ($this->priceId($candidate, $account) === $attributes['plan']) $plan = $candidate->name;
            }
        }
        $attributes['plan'] = $plan ?: ($source ? $source->plan : $attributes['plan']);
        $attributes += ['stripe_id' => $customer, 'subscription_id' => $subscription['id'], 'billing_account' => $account];
        $attributes['paused_at'] = !empty($subscription['pause_collection']) ? ($source && $source->paused_at ? $source->paused_at : now()) : null;
        $invoice = is_array($subscription['latest_invoice'] ?? null) ? $subscription['latest_invoice'] : [];
        $attributes['latest_invoice_id'] = SubscriptionState::id($subscription['latest_invoice'] ?? null);
        $attributes['payment_url'] = $this->invoiceUrl($invoice['hosted_invoice_url'] ?? null);
        $attributes['payment_status'] = $invoice['status'] ?? null;
        $attributes['payment_method_id'] = SubscriptionState::id($subscription['default_payment_method'] ?? null);
        if (array_key_exists('schedule', $subscription)) $attributes['schedule_id'] = SubscriptionState::id($subscription['schedule']);
        $attributes['synced_at'] = now();
        if ($source) {
            if ($source->subscription_id && $source->subscription_id !== $subscription['id']) {
                // Only the purchase path may replace an ended subscription.
                if (!$source->ended_at && !in_array($source->status, ['canceled', 'incomplete_expired'], true)) throw new BillingException('A different subscription is already linked to this account.');
                $attributes += ['pending_plan' => null, 'plan_changes_at' => null, 'schedule_id' => null];
            }
            if ($source->pending_plan === $attributes['plan']) $attributes += ['pending_plan' => null, 'plan_changes_at' => null];
            $source->update($attributes);
        } else {
            $source = Stripe::create($attributes);
            $user->membership()->create(['source_type' => Stripe::class, 'source_id' => $source->id]);
        }
        $user->unsetRelation('membership');
        return $source;
    }

    private function invoiceUrl($url): ?string
    {
        return is_string($url) && preg_match('~^https://invoice\.stripe\.com/~', $url) ? $url : null;
    }

    public function paymentResult(array $subscription, $operation): array
    {
        $status = $subscription['status'];
        $pendingSetup = is_array($subscription['pending_setup_intent'] ?? null) ? $subscription['pending_setup_intent'] : [];
        return ['operation_id' => $operation->id, 'status' => $status,
            'payment_secret' => data_get($subscription, 'latest_invoice.confirmation_secret.client_secret')
                ?? data_get($subscription, 'latest_invoice.payment_intent.client_secret'),
            'subscription_setup_secret' => in_array($pendingSetup['status'] ?? null, ['requires_action', 'requires_confirmation'], true) ? ($pendingSetup['client_secret'] ?? null) : null,
            'complete' => in_array($status, ['active', 'trialing'], true) && (!$pendingSetup || ($pendingSetup['status'] ?? null) === 'succeeded'),
        ];
    }

    public function finish(User $user, string $id): array
    {
        return $this->operations->locked($user, function () use ($user, $id) {
            $operation = $this->operations->owned($user, $id, 'purchase');
            if (!$operation->subscription_id) throw new BillingException('Please finish your checkout first.');
            $source = $this->source($user);
            if ($source->subscription_id !== $operation->subscription_id) throw new BillingException('This checkout no longer matches your membership.');
            $subscription = $this->subscription($user);
            $result = $this->paymentResult($subscription, $operation);
            if (!$result['complete']) throw new BillingException('Your payment has not completed. Please finish the payment or open My Membership for help.');
            $this->operations->complete($id);
            $this->refreshCard($this->source($user), $subscription);
            $result['first_completion'] = $operation->state !== 'complete';
            $result['subscription'] = $subscription;
            $result['customer'] = ['id' => $source->stripe_id];
            return $result;
        });
    }

    public function updateCard(User $user, string $id, string $setup): void
    {
        $this->operations->locked($user, function () use ($user, $id, $setup) {
            $operation = $this->operations->owned($user, $id, 'card');
            if ($operation->state === 'complete') return;
            $source = $this->source($user);
            $intent = $this->verifiedSetup($operation, $setup);
            if ($operation->customer_id !== $source->stripe_id || $operation->billing_account !== $this->accounts->forSource($source)) throw new BillingException('This card confirmation does not match your membership.');
            $subscription = $this->subscription($user);
            $schedule = SubscriptionState::id($subscription['schedule'] ?? null);
            if ($schedule) {
                $record = $this->gateway->call($operation->billing_account, 'subscriptionSchedules', 'retrieve', [], $schedule);
                if (data_get($record, 'metadata.pianolit_membership') !== (string) $source->id) throw new BillingException('Please contact contact@pianolit.com to update the card on this scheduled subscription.');
                $this->gateway->call($operation->billing_account, 'subscriptionSchedules', 'update', [
                    'default_settings' => ['default_payment_method' => SubscriptionState::id($intent['payment_method'])],
                ], $schedule, $id.':schedule-card');
            }
            $this->gateway->call($operation->billing_account, 'subscriptions', 'update', [
                'default_payment_method' => SubscriptionState::id($intent['payment_method']),
                'expand' => ['latest_invoice.confirmation_secret'],
            ], $subscription['id'], $id.':card');
            $subscription = $this->subscription($user);
            $source = $this->source($user);
            $this->refreshCard($source, $subscription);
            $this->operations->complete($id);
        });
    }

    public function refreshCard(Stripe $source, array $subscription): void
    {
        $account = $this->accounts->forSource($source);
        $id = SubscriptionState::id($subscription['default_payment_method'] ?? null);
        $card = null;
        if ($id) {
            $method = $this->gateway->call($account, 'paymentMethods', 'retrieve', [], $id);
            if (SubscriptionState::id($method['customer'] ?? null) === $source->stripe_id) $card = $method['card'] ?? null;
        } else {
            $customer = $this->gateway->call($account, 'customers', 'retrieve', ['expand' => ['invoice_settings.default_payment_method', 'default_source']], $source->stripe_id);
            $method = data_get($customer, 'invoice_settings.default_payment_method');
            if (is_array($method)) { $id = $method['id']; $card = $method['card'] ?? null; }
            elseif (is_array($customer['default_source'] ?? null)) $card = $customer['default_source'];
        }
        $source->update(['payment_method_id' => $id, 'card_brand' => $card['brand'] ?? null, 'card_last_four' => $card['last4'] ?? null]);
    }
}
