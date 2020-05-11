<?php

namespace App\Billing\Sources\Concerns;

trait StripeStates
{
	public function isExpired()
	{
		if (! $this->renews_at && ! $this->membership_ends_at)
			return true;
		
		if ($this->renews_at)
			return ! now()->lte($this->renews_at);

		if ($this->membership_ends_at)
			return ! now()->lte($this->membership_ends_at);
	}

	public function isOnGracePeriod()
	{
		return $this->membership_ends_at && now()->lte($this->membership_ends_at);
	}

	public function isPaused()
	{
		return (bool) $this->paused_at;
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
		return $this->canceled_at && $this->canceled_at->lt(now());
	}

	public function hasCard()
	{
		return $this->card_brand && $this->card_last_four;
	}
}
