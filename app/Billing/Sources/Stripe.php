<?php

namespace App\Billing\Sources;

use App\{PianoLit, User};
use Illuminate\Http\Request;
use App\Contracts\BillingSource;
use Stripe\Customer;
use App\Billing\Membership;
use App\Billing\Sources\Concerns\{StripeStates, StripeActions, StripeElements};
use App\Billing\Factories\StripeFactory;

class Stripe extends PianoLit implements BillingSource
{
	use StripeStates, StripeActions, StripeElements;
	
	protected $table = 'stripe_memberships';
	protected $dates = [
		'membership_ends_at', 
		'renews_at', 
		'ended_at', 
		'paused_at', 
		'canceled_at'
	];

    public function scopeByCustomerId($query, $customerId)
    {
    	return $query->where('stripe_id', $customerId)->firstOrFail();
    }

    public function scopeCustomerExists($query, $customerId)
    {
    	return $query->where('stripe_id', $customerId)->exists();
    }

	public function getPlanNameAttribute()
	{
		return ucfirst($this->plan);
	}

	public function upcomingInvoice()
	{
		return $this->isPaused() ? null : (new StripeFactory)->customer()->upcomingInvoice();
	}

	public function pastInvoices()
	{
		return (new StripeFactory)->customer()->pastInvoices();
	}
}
