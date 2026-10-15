<?php

namespace App\Billing\Sources\Concerns;

use App\User;

trait StripeActions
{
    public function scopeSubscribe($query, User $user, $customer)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $customer) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($user->membership()->count() > 1) throw new \App\Billing\Stripe\BillingException('Your billing records need review before subscribing.');
            if ($user->membership()->where('source_type', '!=', \App\Billing\Sources\Stripe::class)->exists()) {
                throw new \App\Billing\Stripe\BillingException('This account already uses another billing provider.');
            }
            if ($user->hasMembershipWith('App\Billing\Sources\Stripe'))
                return $this->reactivate($customer, $user);

            $source = $this->create([
                'plan' => $customer->subscriptions->data[0]->plan->id,
                'stripe_id' => $customer->id,
                'status' => $customer->subscriptions->data[0]->status,
                'renews_at' => $this->subscriptionEnd($customer->subscriptions->data[0]),
                'card_brand' => $customer->sources->data[0]->brand,
                'card_last_four' => $customer->sources->data[0]->last4
            ]);

            $record = $user->membership()->create([
                'source_id' => $source->id,
                'source_type' => get_class($source)
            ]);

            return $record;
        });
    }

    public function reactivate($customer, ?User $user = null)
    {
        $user = $user ?: auth('web')->user();
        $user->unsetRelation('membership');
        return $user->membership->source->update([
            'plan' => $customer->subscriptions->data[0]->plan->id,
            'stripe_id' => $customer->id,
            'status' => $customer->subscriptions->data[0]->status,
            'renews_at' => $this->subscriptionEnd($customer->subscriptions->data[0]),
            'card_brand' => $customer->sources->data[0]->brand,
            'card_last_four' => $customer->sources->data[0]->last4,
            'ended_at' => null,
            'canceled_at' => null,
            'membership_ends_at' => null,
            'paused_at' => null,
        ]);
    }

    public function updateStatus($payload)
    {

        $payload = \App\Billing\Stripe\SubscriptionState::array($payload);
        $state = \App\Billing\Stripe\SubscriptionState::attributes($payload);
        // Keep the legacy method's public contract; the web service additionally
        // persists subscription identity, account and modern payment metadata.
        $this->update(\Illuminate\Support\Arr::only($state, ['status', 'plan', 'renews_at', 'ended_at', 'canceled_at', 'membership_ends_at']));
        $this->updateBillingStatus($payload['pause_collection'] ?? null);
    }

    private function subscriptionEnd($subscription)
    {
        $payload = \App\Billing\Stripe\SubscriptionState::array($subscription);
        $end = $payload['current_period_end'] ?? data_get($payload, 'items.data.0.current_period_end') ?? $payload['trial_end'] ?? null;
        return $end ? carbon($end) : null;
    }


    public function updateBillingStatus($record = null)
    {
        $stripeIsActive = is_null($record);
        $localIsActive = is_null($this->paused_at);

        if ($localIsActive == $stripeIsActive)
            return null;

        $this->update(['paused_at' => $stripeIsActive ? null : now()]);
    }

    public function updateCard($card)
    {
        $this->update([
            'card_brand' => $card['brand'],
            'card_last_four' => $card['last4']
        ]);
    }

    public function cancelAtPeriodEnd($payload)
    {
        $this->update([
            'canceled_at' => now(),
            'membership_ends_at' => $payload['current_period_end']
        ]);
    }
}
