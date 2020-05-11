<?php

namespace App\Billing\Sources;

use App\{PianoLit, User};
use Illuminate\Http\Request;
use App\Contracts\BillingSource;
use Stripe\Customer;
use App\Billing\Sources\Concerns\{StripeStates, StripeActions};
use App\Billing\Factories\StripeFactory;

class Stripe extends PianoLit implements BillingSource
{
	use StripeStates, StripeActions;
	
	protected $table = 'stripe_memberships';
	protected $dates = [
		'membership_ends_at', 
		'renews_at', 
		'ended_at', 
		'paused_at', 
		'canceled_at'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

    public function scopeByCustomerId($query, $customerId)
    {
    	return $query->where('stripe_id', $customerId)->firstOrFail();
    }

	public function getPlanNameAttribute()
	{
		return ucfirst($this->plan);
	}

	public function badge()
	{
		$status = $this->getStatus();

		if ($this->isOnGracePeriod()) {
			$color = 'warning';
			$label = strtoupper($status) . ' until ' . $this->membership_ends_at->format('M jS');
		} elseif ($status == 'active' || $status == 'trial') {
			$color = 'green';
			$label = strtoupper($status);
		} else {
			$color = 'grey';
			$label = 'INACTIVE';
		}

		return '<span class="badge badge-pill alert-'.$color.'">'.$label.'</span>';
	}

    public function card()
    {
    	if ($this->hasCard())
        	return ucfirst($this->card_brand) . ' &middot;&middot;&middot;&middot; ' . $this->card_last_four;

        return null;
    }

	public function getStatus($callStripe = false)
	{
		$trialStates = ['trialing'];
		$activeStates = ['active', 'incomplete'];
		$expiredStates = ['incomplete_expired', 'past_due', 'unpaid'];
		$canceledStates = ['canceled'];

		if (in_array($this->status, $trialStates))
			return 'trial';

		if (in_array($this->status, $activeStates))
			return 'active';

		if (in_array($this->status, $expiredStates))
			return 'expired';

		if (in_array($this->status, $canceledStates))
			return 'canceled';

		return 'inactive';
	}

	public function upcomingInvoice()
	{
		return (new StripeFactory)->customer()->upcomingInvoice();
	}

	public function pastInvoices()
	{
		return (new StripeFactory)->customer()->pastInvoices();
	}
}
