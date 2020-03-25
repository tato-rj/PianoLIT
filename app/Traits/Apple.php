<?php

namespace App\Traits;

trait Apple
{
	protected $errors = [
			21000 => 'The App Store could not read the JSON object you provided.',
			21002 => 'The data in the receipt-data property was malformed or missing.',
			21003 => 'The receipt could not be authenticated.',
			21004 => 'The shared secret you provided does not match the shared secret on file for your account.',
			21005 => 'The receipt server is not currently available.',
			21006 => 'This receipt is valid but the subscription has expired. When this status code is returned to your server, the receipt data is also decoded and returned as part of the response. Only returned for iOS 6 style transaction receipts for auto-renewable subscriptions.',
			21007 => 'This receipt is from the test environment, but it was sent to the production environment for verification. Send it to the test environment instead.',
			21008 => 'This receipt is from the production environment, but it was sent to the test environment for verification. Send it to the production environment instead.',
			21010 => 'This receipt could not be authorized. Treat this the same as if a purchase was never made.'
		];

	public function appleError($number)
	{
		if (! array_key_exists($number, $this->errors))
			return 'Unknown error.';

		return $this->errors[$number];
	}

	public function getLatestReceiptInfoAttribute($value)
	{
		return json_decode($value);
	}

	public function getExpirationIntentTextAttribute()
	{
		$reasons = [
			null,
			'Customer canceled their subscription.', 
			'Billing error; for example customer’s payment information was no longer valid.', 
			'Customer did not agree to a recent price increase.', 
			'Product was not available for purchase at the time of renewal.', 
			'Unknown error'];

		return $reasons[$this->expiration_intent];
	}

	public function expired()
	{
		if (! $this->renews_at)
			return false;
		
		return ! now()->lte($this->renews_at);
	}

	public function scopeLocate($query, $subscriptionId)
	{
		return $query->where('original_transaction_id', $subscriptionId)->first();
	}
}
