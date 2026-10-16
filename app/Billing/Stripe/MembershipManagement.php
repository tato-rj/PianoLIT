<?php

namespace App\Billing\Stripe;

use App\User;
use App\Billing\Plan;

class MembershipManagement
{
    private $billing, $gateway, $operations, $accounts;

    public function __construct(MembershipBilling $billing, Gateway $gateway, Operations $operations, Accounts $accounts)
    {
        $this->billing = $billing;
        $this->gateway = $gateway;
        $this->operations = $operations;
        $this->accounts = $accounts;
    }

    public function change(User $user, string $id, string $action, ?Plan $plan = null): void
    {
        $source = $this->billing->source($user);
        $account = $this->accounts->forSource($source);
        $operation = $this->operations->begin($user, $id, $action, $account, $plan ? $plan->name : null);
        $this->operations->locked($user, function () use ($user, $operation, $action, $plan, $account) {
            $operation = $this->operations->owned($user, $operation->id, $action);
            if ($operation->state === 'complete') return;
            $subscription = $this->billing->subscription($user);
            $source = $this->billing->source($user);
            $this->operations->update($operation->id, ['customer_id' => $source->stripe_id, 'subscription_id' => $subscription['id']]);
            if (in_array($subscription['status'], ['canceled', 'incomplete_expired'], true)) throw new BillingException('This subscription has ended. Select a plan to subscribe again.');
            if ($action === 'plan') {
                if (!in_array($subscription['status'], ['active', 'trialing'], true) || !empty($subscription['cancel_at_period_end'])
                    || !empty($subscription['cancel_at']) || !empty($subscription['pause_collection'])) {
                    throw new BillingException('Resume your membership and resolve any payment issues before changing plans.');
                }
                $this->schedulePlan($user, $subscription, $plan, $operation);
            } else {
                if (!in_array($action, ['pause', 'unpause', 'cancel', 'resume'], true)) throw new \InvalidArgumentException('Unknown billing action.');
                if ($action === 'cancel') $this->releaseSchedule($user, $subscription, $operation);
                if ($action === 'resume' && !empty($subscription['cancel_at']) && empty($subscription['cancel_at_period_end'])) {
                    throw new BillingException('Please contact contact@pianolit.com to change this scheduled cancellation.');
                }
                $parameters = in_array($action, ['pause', 'unpause'], true)
                    ? ['pause_collection' => $action === 'pause' ? ['behavior' => 'void'] : '']
                    : ['cancel_at_period_end' => $action === 'cancel'];
                if ($action === 'pause') {
                    if (!in_array($subscription['status'], ['active', 'trialing'], true) || !empty($subscription['cancel_at_period_end']) || !empty($subscription['cancel_at'])) {
                        throw new BillingException('Resolve your payment or cancellation before pausing billing.');
                    }
                    $open = $this->gateway->call($account, 'invoices', 'all', ['customer' => $source->stripe_id, 'subscription' => $subscription['id'], 'status' => 'open', 'limit' => 1]);
                    if (!empty($open['data'])) throw new BillingException('An unpaid invoice is still open. Please resolve it before pausing billing.');
                }
                if ($action === 'cancel' && $subscription['status'] === 'incomplete') {
                    // An unpaid first invoice has no paid period to preserve.
                    $this->gateway->call($account, 'subscriptions', 'cancel', ['invoice_now' => false, 'prorate' => false], $subscription['id'], $operation->id.':'.$action);
                } else {
                    $this->gateway->call($account, 'subscriptions', 'update', $parameters + ['expand' => ['latest_invoice.confirmation_secret']], $subscription['id'], $operation->id.':'.$action);
                }
                $this->billing->subscription($user);
                if ($action === 'cancel') {
                    $this->billing->source($user)->update(['pending_plan' => null, 'plan_changes_at' => null, 'schedule_id' => null]);
                    \Illuminate\Support\Facades\DB::table('stripe_billing_operations')->where('user_id', $user->id)->where('active_key', 'purchase:'.$user->id)
                        ->update(['active_key' => null, 'state' => 'canceled', 'updated_at' => now()]);
                }
            }
            $this->operations->complete($operation->id);
        });
    }

    private function schedulePlan(User $user, array $subscription, Plan $plan, $operation): void
    {
        $source = $this->billing->source($user);
        $account = $this->accounts->forSource($source);
        $price = $this->billing->price($plan, $account);
        $current = data_get($subscription, 'items.data', []);
        if (count($current) !== 1 || !SubscriptionState::periodEnd($subscription)) throw new BillingException('Please contact contact@pianolit.com to change this subscription.');
        if ($source->plan === $plan->name) {
            if ($source->pending_plan) {
                $this->releaseSchedule($user, $subscription, $operation);
                $source->update(['pending_plan' => null, 'plan_changes_at' => null, 'schedule_id' => null]);
            }
            return;
        }
        $scheduleId = SubscriptionState::id($subscription['schedule'] ?? null);
        if ($scheduleId) {
            $schedule = $this->gateway->call($account, 'subscriptionSchedules', 'retrieve', [], $scheduleId);
            if (data_get($schedule, 'metadata.pianolit_membership') !== (string) $source->id) {
                if (data_get($schedule, 'metadata.pianolit_membership')) throw new BillingException('This subscription already has a billing schedule. Please contact contact@pianolit.com to change it.');
                // Recover a schedule created by this same request if its second
                // provider call failed. A different request cannot adopt it:
                // Stripe rejects creating another schedule on that subscription.
                $recovered = $this->gateway->call($account, 'subscriptionSchedules', 'create', ['from_subscription' => $subscription['id']], null, $operation->id.':schedule');
                if ($recovered['id'] !== $scheduleId) throw new BillingException('This billing schedule needs review. Please contact contact@pianolit.com.');
            }
        } else {
            $schedule = $this->gateway->call($account, 'subscriptionSchedules', 'create', [
                'from_subscription' => $subscription['id'],
            ], null, $operation->id.':schedule');
        }
        $phase = $schedule['phases'][0];
        $start = data_get($schedule, 'current_phase.start_date') ?: $phase['start_date'];
        foreach ($schedule['phases'] as $candidate) {
            if ($candidate['start_date'] === $start) $phase = $candidate;
        }
        // Preserve existing discounts by their discount IDs, including their
        // original duration, and preserve the other supported phase settings.
        $settings = array_filter(\Illuminate\Support\Arr::only($phase, ['collection_method', 'default_tax_rates', 'automatic_tax', 'application_fee_percent', 'on_behalf_of', 'transfer_data', 'invoice_settings']), function ($value) { return $value !== null; });
        foreach (['default_payment_method', 'on_behalf_of'] as $key) {
            if (isset($settings[$key])) $settings[$key] = SubscriptionState::id($settings[$key]);
        }
        if (isset($settings['default_tax_rates'])) $settings['default_tax_rates'] = array_map([SubscriptionState::class, 'id'], $settings['default_tax_rates']);
        if (isset($settings['automatic_tax'])) {
            // Stripe includes response-only fields such as disabled_reason.
            // A schedule update accepts only enabled and the tax liability.
            $tax = \Illuminate\Support\Arr::only($settings['automatic_tax'], ['enabled', 'liability']);
            if (isset($tax['liability'])) {
                $tax['liability'] = \Illuminate\Support\Arr::only($tax['liability'], ['type', 'account']);
                if (isset($tax['liability']['account'])) $tax['liability']['account'] = SubscriptionState::id($tax['liability']['account']);
            } else {
                unset($tax['liability']);
            }
            $settings['automatic_tax'] = $tax;
        }
        $discounts = [];
        foreach ($phase['discounts'] ?? [] as $discount) {
            $existing = SubscriptionState::id($discount['discount'] ?? null);
            if ($existing) $discounts[] = ['discount' => $existing];
            elseif ($coupon = SubscriptionState::id($discount['coupon'] ?? null)) $discounts[] = ['coupon' => $coupon];
        }
        if ($discounts) $settings['discounts'] = $discounts;
        $items = [];
        foreach ($phase['items'] as $item) {
            $copy = ['price' => SubscriptionState::id($item['price'] ?? $item['plan']), 'quantity' => $item['quantity'] ?? 1];
            if (!empty($item['tax_rates'])) $copy['tax_rates'] = array_map([SubscriptionState::class, 'id'], $item['tax_rates']);
            if (!empty($item['discounts'])) throw new BillingException('Please contact contact@pianolit.com to preserve this item discount when changing plans.');
            if (!empty($item['billing_thresholds'])) $copy['billing_thresholds'] = $item['billing_thresholds'];
            $items[] = $copy;
        }
        $end = SubscriptionState::periodEnd($subscription);
        $first = $settings + ['items' => $items, 'start_date' => $start, 'end_date' => $end, 'proration_behavior' => 'none'];
        if (!empty($subscription['trial_end']) && $subscription['trial_end'] > now()->timestamp) $first['trial_end'] = min($subscription['trial_end'], $end);
        $nextItem = array_replace($items[0], ['price' => $price['id']]);
        $second = $settings + ['items' => [$nextItem], 'start_date' => $end, 'iterations' => 1, 'proration_behavior' => 'none', 'billing_cycle_anchor' => 'phase_start'];
        $this->gateway->call($account, 'subscriptionSchedules', 'update', [
            'end_behavior' => 'release', 'metadata' => ['pianolit_membership' => (string) $source->id], 'proration_behavior' => 'none', 'phases' => [$first, $second],
        ], $schedule['id'], $operation->id.':phases');
        $latest = $this->gateway->call($account, 'subscriptionSchedules', 'retrieve', [], $schedule['id']);
        $next = null;
        foreach ($latest['phases'] as $candidate) {
            if ($candidate['start_date'] >= $end) { $next = $candidate; break; }
        }
        $pending = null;
        foreach (Plan::all() as $candidate) {
            if ($this->billing->priceId($candidate, $account) === SubscriptionState::id(data_get($next, 'items.0.price'))) $pending = $candidate->name;
        }
        $source->update(['pending_plan' => $pending, 'plan_changes_at' => $pending ? carbon($next['start_date']) : null, 'schedule_id' => $schedule['id']]);
    }

    private function releaseSchedule(User $user, array $subscription, $operation): void
    {
        $id = SubscriptionState::id($subscription['schedule'] ?? null);
        if (!$id) return;
        $source = $this->billing->source($user);
        $schedule = $this->gateway->call($operation->billing_account, 'subscriptionSchedules', 'retrieve', [], $id);
        if (data_get($schedule, 'metadata.pianolit_membership') !== (string) $source->id) throw new BillingException('Please contact contact@pianolit.com to cancel this scheduled subscription.');
        $this->gateway->call($operation->billing_account, 'subscriptionSchedules', 'release', [], $id, $operation->id.':release');
    }

    public function invoices(User $user, ?string $after = null): array
    {
        return $this->operations->locked($user, function () use ($user, $after) {
            $subscription = $this->billing->subscription($user);
            $source = $this->billing->source($user);
            $account = $this->accounts->forSource($source);
            // A returning member keeps invoices from earlier subscriptions on this same customer.
            $parameters = ['customer' => $source->stripe_id, 'limit' => 12];
            if ($after) $parameters['starting_after'] = $after;
            $past = $this->gateway->call($account, 'invoices', 'all', $parameters);
            $upcoming = null;
            if (!$after && in_array($subscription['status'], ['active', 'trialing'], true) && empty($subscription['pause_collection'])
                && empty($subscription['cancel_at_period_end']) && empty($subscription['cancel_at'])) {
                $upcoming = $this->gateway->call($account, 'invoices', 'createPreview', ['customer' => $source->stripe_id, 'subscription' => $subscription['id']]);
            }
            return ['invoices' => $past['data'], 'has_more' => $past['has_more'] ?? false, 'upcoming' => $upcoming];
        });
    }
}
