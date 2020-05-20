<?php

namespace App\Billing\Sources\Concerns;

trait StripeElements
{
	public function badge()
	{
		$status = $this->getStatus();
		$color = $this->getColor();
		$icon = $this->isPaused() ? '<i class="fas fa-pause-circle mr-1"></i>' : null;

		return '<span class="badge badge-pill alert-'.$color.'">'.$icon.strtoupper($status).'</span>';
	}

    public function card()
    {
    	if ($this->hasCard())
        	return ucfirst($this->card_brand) . ' &middot;&middot;&middot;&middot; ' . $this->card_last_four;

        return null;
    }
}
