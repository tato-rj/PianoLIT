<?php

namespace App\Billing\Sources;

use App\{PianoLit, User};
use Illuminate\Http\Request;
use App\Contracts\BillingSource;
use Stripe\Customer;
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

	public function upcomingInvoice()
	{
		return $this->isPaused() ? null : (new StripeFactory)->customer()->upcomingInvoice();
	}

	public function pastInvoices()
	{
		return (new StripeFactory)->customer()->pastInvoices();
	}
}
