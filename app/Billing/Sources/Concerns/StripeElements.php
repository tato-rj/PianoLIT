<?php

namespace App\Billing\Sources\Concerns;

trait StripeElements
{
	public function badge()
	{
		$status = $this->getStatus();
		$color = $this->getColor();
		$icon = $this->isPaused() ? '<i class="fas fa-pause-circle mr-1"></i>' : null;
		$append = $this->isOnGracePeriod() ? ' expires on ' . $this->membership_ends_at->format('n/j') : null;

		return '<span class="text-nowrap badge badge-pill alert-'.$color.'">'.$icon.strtoupper($status).$append.'</span>';
	}

    public function card()
    {
    	if ($this->hasCard())
        	return ucfirst($this->card_brand) . ' &middot;&middot;&middot;&middot; ' . $this->card_last_four;

        return null;
    }
}
