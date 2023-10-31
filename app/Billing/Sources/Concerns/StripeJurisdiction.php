<?php

namespace App\Billing\Sources\Concerns;

class StripeJurisdiction
{
	public function us()
	{
		config(['services.stripe.key' => config('services.stripe.us_key')]);
		config(['services.stripe.secret' => config('services.stripe.us_secret')]);
	}

	public function swiss()
	{
		config(['services.stripe.key' => config('services.stripe.swiss_key')]);
		config(['services.stripe.secret' => config('services.stripe.swiss_secret')]);
	}

	public function set()
	{
	    if (auth()->user()->isSwissCustomer()) {
	      return $this->swiss();
	    } else {
	      return $this->us();
	    }
	}
}