<?php

namespace App\Billing\Sources\Concerns;

use App\User;

trait StripeActions
{
	public function scopeSubscribe($query, User $user, $customer)
	{
		if ($user->hasMembershipWith('App\Billing\Sources\Stripe')) 
			return $this->reactivate($customer);

        $source = $this->create([
            'plan' => $customer->subscriptions->data[0]->plan->id,
            'stripe_id' => $customer->id,
            'status' => $customer->subscriptions->data[0]->status,
            'renews_at' => carbon($customer->subscriptions->data[0]->trial_end),
            'card_brand' => $customer->sources->data[0]->brand,
            'card_last_four' => $customer->sources->data[0]->last4
        ]);

        $record = $user->membership()->create([
            'source_id' => $source->id,
            'source_type' => get_class($source)
        ]);

        return $record;
	}

	public function reactivate($customer)
	{
		return auth()->user()->membership->source->update([
            'plan' => $customer->subscriptions->data[0]->plan->id,
            'stripe_id' => $customer->id,
            'status' => $customer->subscriptions->data[0]->status,
            'renews_at' => carbon($customer->subscriptions->data[0]->trial_end),
            'card_brand' => $customer->sources->data[0]->brand,
            'card_last_four' => $customer->sources->data[0]->last4,
            'ended_at' => null,
            'canceled_at' => null
		]);
	}

	public function updateStatus($payload)
	{
		$ended = isset($payload['ended_at']) && $payload['ended_at'];
		$scheduledEnd = isset($payload['cancel_at']) && $payload['cancel_at'];
		$naturalEnd = isset($payload['cancel_at_period_end']) && $payload['cancel_at_period_end'];
		$willNotRenew = $ended || $scheduledEnd || $naturalEnd;

		$this->update([
			'status' => $payload['status'],
			'plan' => $payload['plan']['id'],
			'renews_at' => $willNotRenew ? null : $payload['current_period_end'],
			'ended_at' => $payload['ended_at'] ?? null,
			'membership_ends_at' => $scheduledEnd || $naturalEnd ? $payload['current_period_end'] : null,	
		]);

		$this->updateBillingStatus($payload['pause_collection'] ?? null);
	}

	public function updateBillingStatus($record = null)
	{
		$stripeIsActive = is_null($record);
		$localIsActive = is_null($this->paused_at);

		if ($localIsActive == $stripeIsActive)
			return null;

		$this->update(['paused_at' => $stripeIsActive ? null : now()]);
	}

	public function updateCard($playload)
	{
		$this->update([
			'card_brand' => $payload->sources->data[0]->brand,
			'card_last_four' => $payload->sources->data[0]->last4
		]);
	}

	public function cancelAtPeriodEnd($payload)
	{
		$this->update([
			'status' => 'canceled',
			'canceled_at' => now(),
			'membership_ends_at' => $payload['current_period_end']
		]);
	}

	public function cancel($payload)
	{
		$this->update([
			'status' => $payload['status'],
			'ended_at' => $payload['ended_at'] ?? null,
			'canceled_at' => $payload['canceled_at'] ?? null,
			'membership_ends_at' => null,
			'paused_at' => null,
			'card_brand' => null,
			'card_last_four' => null
		]);
	}
}
