<?php

namespace App\Services;

use App\Activity\ExcludedAccounts;
use App\Analytics\GoogleAnalyticsConversions;
use App\Billing\Stripe\{Gateway, SubscriptionState};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Verified web membership facts only. No pageviews, browser counts or billing mutations. */
class CampaignConversions
{
    public function reconcile(int $limit = 100): void
    {
        if (!config('attribution.conversion_links_enabled')) return;
        $contexts = DB::table('campaign_checkout_contexts as c')->join('stripe_billing_operations as o', 'o.id', '=', 'c.operation_id')
            ->join('campaign_visits as v', 'v.id', '=', 'c.visit_id')->whereNull('c.linked_at')->whereNotNull('o.subscription_id')
            ->where('o.kind', 'purchase')->whereNotNull('v.consented_at')->whereColumn('v.user_id', 'o.user_id')
            ->select('c.*', 'o.user_id', 'o.billing_account', 'o.customer_id', 'o.subscription_id', 'o.plan')
            ->orderBy('c.created_at')->limit($limit)->get();
        foreach ($contexts as $context) {
            if (ExcludedAccounts::id($context->user_id) || !DB::table('users')->where('id', $context->user_id)->exists()) continue;
            try {
                $subscription = app(Gateway::class)->call($context->billing_account, 'subscriptions', 'retrieve', [], $context->subscription_id);
                if (($subscription['id'] ?? null) !== $context->subscription_id || SubscriptionState::id($subscription['customer'] ?? null) !== $context->customer_id) continue;
                if ($this->link($context->visit_id, $context->user_id, $context->billing_account, $subscription, $context->plan)) {
                    DB::table('campaign_checkout_contexts')->where('operation_id', $context->operation_id)->update(['linked_at' => now('UTC')]);
                }
            } catch (\Throwable $exception) {
                // Try again on the next scheduler run. Provider exceptions may
                // contain private response data, so do not log their bodies here.
            }
        }
        $links = DB::table('campaign_subscriptions as s')->where('s.google_live', true)
            ->whereNotExists(function ($query) { $query->from('google_analytics_conversions as g')->selectRaw('1')->whereColumn('g.campaign_subscription_id', 's.id')->where('g.kind', 'first_payment'); })
            ->whereExists(function ($query) { $query->from('stripe_payment_receipts as p')->selectRaw('1')->where('p.kind', 'invoice')->where('p.amount', '>', 0)->whereColumn('p.stripe_customer_id', 's.stripe_customer_id')->whereColumn('p.stripe_subscription_id', 's.stripe_subscription_id'); })
            ->orderBy('s.id')->limit($limit)->get();
        foreach ($links as $link) GoogleAnalyticsConversions::enqueueForSubscription($link->stripe_subscription_id, $link->stripe_customer_id);
    }

    public function link(string $visitId, int $userId, string $account, array $subscription, ?string $plan): bool
    {
        $customer = SubscriptionState::id($subscription['customer'] ?? null);
        if (!in_array($account, ['us', 'swiss'], true) || !self::providerId($subscription['id'] ?? null, 'sub_') || !self::providerId($customer, 'cus_')
            || !in_array($subscription['status'] ?? null, ['trialing', 'active', 'incomplete'], true)
            || !is_int($subscription['created'] ?? null) || $subscription['created'] <= 0 || ExcludedAccounts::id($userId)) return false;
        return DB::transaction(function () use ($visitId, $userId, $account, $subscription, $customer, $plan) {
            // All modern checkout links must match an operation owned by this
            // account, customer and billing account; a submitted ID proves none of this.
            $operation = DB::table('stripe_billing_operations')->where('user_id', $userId)->where('kind', 'purchase')
                ->where('subscription_id', $subscription['id'])->where('customer_id', $customer)->where('billing_account', $account)->first();
            if (!$operation) return false;
            $visit = DB::table('campaign_visits')->where('id', $visitId)->where('user_id', $userId)->whereNotNull('consented_at')->lockForUpdate()->first();
            if (!$visit) return false;
            $created = Carbon::createFromTimestamp($subscription['created'], config('app.timezone'));
            if ($created->lt(carbon($visit->created_at)->subMinute()) || $created->gt(carbon($visit->expires_at))) return false;
            $trial = $subscription['status'] !== 'incomplete' && is_int($subscription['trial_start'] ?? null) && is_int($subscription['trial_end'] ?? null)
                && ($subscription['status'] === 'trialing' || $subscription['trial_end'] <= now()->timestamp)
                && $subscription['trial_start'] >= $subscription['created'] && $subscription['trial_end'] > $subscription['trial_start']
                ? Carbon::createFromTimestamp($subscription['trial_start'], config('app.timezone')) : null;
            DB::table('campaign_subscriptions')->insertOrIgnore([
                'visit_id' => $visitId, 'stripe_subscription_id' => $subscription['id'], 'stripe_customer_id' => $customer,
                'billing_account' => $account, 'plan' => $plan ?: '', 'trial_started_at' => $trial, 'subscribed_at' => $created,
                'google_live' => ($subscription['livemode'] ?? false) === true,
            ]);
            $link = DB::table('campaign_subscriptions')->where('stripe_subscription_id', $subscription['id'])->first();
            if (!$link || $link->visit_id !== $visitId || $link->stripe_customer_id !== $customer || $link->billing_account !== $account) return false;
            if (($subscription['livemode'] ?? false) === true) DB::table('campaign_subscriptions')->where('id', $link->id)->update(['google_live' => true]);
            if (!$link->trial_started_at && $trial) DB::table('campaign_subscriptions')->where('id', $link->id)->update(['trial_started_at' => $trial]);
            GoogleAnalyticsConversions::enqueueForSubscription($subscription['id'], $customer);
            return true;
        });
    }

    /** Read the provider's complete paid-invoice history, not webhook arrival order. */
    public function firstPayment($link): ?array
    {
        $after = null; $seen = []; $first = null;
        for ($page = 0; $page < 10; $page++) {
            $parameters = ['customer' => $link->stripe_customer_id, 'subscription' => $link->stripe_subscription_id, 'status' => 'paid', 'limit' => 100];
            if ($after) $parameters['starting_after'] = $after;
            $result = app(Gateway::class)->call($link->billing_account, 'invoices', 'all', $parameters);
            if (!is_array($result['data'] ?? null) || !is_bool($result['has_more'] ?? null)) throw new \RuntimeException('incomplete_invoice_history');
            foreach ($result['data'] as $invoice) {
                $id = $invoice['id'] ?? null;
                $subscription = SubscriptionState::id($invoice['subscription'] ?? data_get($invoice, 'parent.subscription_details.subscription'));
                if (!self::providerId($id, 'in_') || isset($seen[$id]) || SubscriptionState::id($invoice['customer'] ?? null) !== $link->stripe_customer_id
                    || $subscription !== $link->stripe_subscription_id || ($invoice['status'] ?? null) !== 'paid'
                    || ($invoice['paid'] ?? false) !== true || !is_int($invoice['amount_paid'] ?? null) || $invoice['amount_paid'] < 0) {
                    throw new \RuntimeException('ambiguous_invoice_history');
                }
                $seen[$id] = true; $after = $id;
                if ($invoice['amount_paid'] === 0) continue;
                $at = data_get($invoice, 'status_transitions.paid_at');
                if (!is_int($at) || $at <= 0 || ($invoice['livemode'] ?? false) !== true) throw new \RuntimeException('unverified_payment');
                if (!$first || [$at, $id] < [$first['at'], $first['id']]) $first = ['id' => $id, 'at' => $at, 'amount' => $invoice['amount_paid'], 'currency' => $invoice['currency'] ?? null];
            }
            if (!$result['has_more']) return $first;
            if (!$result['data']) break;
        }
        throw new \RuntimeException('incomplete_invoice_history');
    }

    public static function providerId($value, string $prefix): bool
    {
        return is_string($value) && strlen($value) <= 255 && preg_match('/^'.preg_quote($prefix, '/').'[A-Za-z0-9]+$/D', $value);
    }
}
