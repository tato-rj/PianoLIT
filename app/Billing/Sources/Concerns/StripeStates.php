<?php

namespace App\Billing\Sources\Concerns;

trait StripeStates
{
	protected $states = [
		'active' => [
			'trialing',
			'trial',
			'active',
		],
		'inactive' => [
			'incomplete',
			'paused',
		],
		'canceled' => [
			'canceled',
			'unpaid',
			'incomplete_expired',
			'past_due',
		]
	];

	public function getColor()
	{
		if ($this->isOnGracePeriod())
			return 'warning';

		$status = $this->getStatus();

		if (in_array($status, $this->states['inactive']) || $this->paused_at)
			return 'secondary';

		if (in_array($status, $this->states['active']))
			return 'green';
		
		if (in_array($status, $this->states['canceled']))
			return 'danger';
	}

	public function getStatus($callStripe = false)
	{
		if ($this->isPaused()) {
			return $this->isExpired() ? 'inactive' : 'active';
		}

		if ($this->isEnded())
			return 'inactive';
	
		if ($this->isOnTrial())
			return 'trial';

		return str_replace('_', ' ', $this->status);
	}

	public function isActive()
	{
		return ! $this->isPaused() && in_array($this->status, $this->states['active']);
	}

	public function isExpired()
	{
		if (! $this->renews_at && ! $this->membership_ends_at)
			return true;
		
		if ($this->renews_at)
			return ! now()->lte($this->renews_at);

		if ($this->membership_ends_at)
			return ! now()->lte($this->membership_ends_at);
	}

	public function isOnTrial()
	{
		return $this->status == 'trialing';
	}

	public function isOnGracePeriod()
	{
		return $this->membership_ends_at && now()->lte($this->membership_ends_at);
	}

	public function isPaused()
	{
		return (bool) $this->paused_at && ! $this->isEnded();
	}

	public function isEnded()
	{
		return (bool) $this->ended_at;
	}

	public function willRenew()
	{
		return (bool) $this->renews_at;
	}

	public function isCanceled()
	{
		return (bool) $this->canceled_at;
	}

	public function hasCard()
	{
		return $this->card_brand && $this->card_last_four;
	}
}
