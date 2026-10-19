<?php

namespace App\Billing\Stripe;

use App\Billing\Sources\Stripe;

class SubscriptionResolutionException extends BillingException
{
    private $details, $endedHistory;

    public function __construct(Stripe $source, string $account, array $subscriptions)
    {
        parent::__construct('Your subscription needs a billing review. Please contact contact@pianolit.com.');

        $this->details = [
            'source_id' => $source->id, 'billing_account' => $account, 'customer_id' => $source->stripe_id,
            'local_status' => $source->status, 'subscription_count' => count($subscriptions),
            'subscriptions' => array_map(function ($subscription) {
                return ['id' => $subscription['id'] ?? null, 'status' => $subscription['status'] ?? null];
            }, $subscriptions),
        ];

        // Only a locally ended source with several verified ended subscriptions
        // is ordinary historical ambiguity. Empty results or any live candidate
        // still need review. Reconciliation rechecks Stripe on every rotation.
        $this->endedHistory = !$source->subscription_id && count($subscriptions) > 1
            && in_array($source->status, ['canceled', 'incomplete_expired'], true)
            && (!$source->membership_ends_at || $source->membership_ends_at->lt(now()))
            && (!$source->renews_at || $source->renews_at->lt(now()));
        foreach ($subscriptions as $subscription) {
            if (empty($subscription['id']) || SubscriptionState::id($subscription['customer'] ?? null) !== $source->stripe_id
                || !in_array($subscription['status'] ?? null, ['canceled', 'incomplete_expired'], true)) {
                $this->endedHistory = false;
            }
        }
    }

    public function isEndedHistory(): bool
    {
        return $this->endedHistory;
    }

    public function context(): array
    {
        // Laravel passes this context to both the local log and Bugsnag. Never
        // include the full provider response, card details or contact data.
        return ['stripe_membership' => $this->details];
    }

    public function diagnostic(): string
    {
        $subscriptions = array_map(function ($subscription) {
            return ($subscription['id'] ?? 'unknown').':'.($subscription['status'] ?? 'unknown');
        }, $this->details['subscriptions']);
        return 'source '.$this->details['source_id'].' / '.$this->details['billing_account'].' / '.$this->details['customer_id']
            .' / local '.$this->details['local_status'].' / subscriptions ['.implode(', ', $subscriptions).']';
    }
}
