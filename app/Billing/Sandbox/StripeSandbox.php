<?php

namespace App\Billing\Sandbox;

use App\Billing\Sandbox\Traits\StripeEvents;
use App\Billing\Factories\StripeFactory;
use Stripe\{Stripe, Token};

class StripeSandbox
{
	use StripeEvents;

	protected $customerId;

	public function customerId($customerId)
	{
		$this->customerId = $customerId;

		return $this;
	}

	public function token($card = '4242424242424242')
	{
        Stripe::setApiKey(config('services.stripe.secret'));

	    return Token::create([
	      'card' => [
	        'number' => $card,
	        'exp_month' => 1,
	        'exp_year' => now()->addYears(10)->year,
	        'cvc' => 123
	      ]
	    ])->id;
	}

	public function getEvent($event)
	{
		if (method_exists($this, $event))
			return $this->$event();

		abort(404, 'The event ' . $event . ' does not exist');
	}

	public function getCustomer()
	{
		return json_decode('{
			"id": "cus_HEJe1orDMGiLrh",
			"object": "customer",
			"address": null,
			"balance": 0,
			"created": 1588786005,
			"currency": "usd",
			"default_source": "card_1Gfr16GQzApYr7LGPks9Vjxd",
			"delinquent": false,
			"description": "Arthur Villar",
			"discount": null,
			"email": "arthurvillar@gmail.com",
			"invoice_prefix": "FE3C5234",
			"invoice_settings": {
			"custom_fields": null,
			"default_payment_method": null,
			"footer": null
			},
			"livemode": false,
			"metadata": [],
			"name": null,
			"next_invoice_sequence": 2,
			"phone": null,
			"preferred_locales": [],
			"shipping": null,
			"sources": {
			"object": "list",
			"data": [
			{
			"id": "card_1Gfr16GQzApYr7LGPks9Vjxd",
			"object": "card",
			"address_city": null,
			"address_country": null,
			"address_line1": null,
			"address_line1_check": null,
			"address_line2": null,
			"address_state": null,
			"address_zip": null,
			"address_zip_check": null,
			"brand": "Visa",
			"country": "US",
			"customer": "cus_HEJe1orDMGiLrh",
			"cvc_check": "pass",
			"dynamic_last4": null,
			"exp_month": 4,
			"exp_year": 2022,
			"fingerprint": "Uiloio5G8qOMuIOE",
			"funding": "credit",
			"last4": "4242",
			"metadata": [],
			"name": null,
			"tokenization_method": null
			}
			],
			"has_more": false,
			"total_count": 1,
			"url": "/v1/customers/cus_HEJe1orDMGiLrh/sources"
			},
			"subscriptions": {
			"object": "list",
			"data": [
			{
			"id": "sub_HEJe6gDoQT20Iq",
			"object": "subscription",
			"application_fee_percent": null,
			"billing_cycle_anchor": 1589390805,
			"billing_thresholds": null,
			"cancel_at": null,
			"cancel_at_period_end": false,
			"canceled_at": null,
			"collection_method": "charge_automatically",
			"created": 1588786005,
			"current_period_end": 1589390805,
			"current_period_start": 1588786005,
			"customer": "cus_HEJe1orDMGiLrh",
			"days_until_due": null,
			"default_payment_method": null,
			"default_source": null,
			"default_tax_rates": [],
			"discount": null,
			"ended_at": null,
			"items": {
			"object": "list",
			"data": [
			{
			"id": "si_HEJeIl1mnyJQL5",
			"object": "subscription_item",
			"billing_thresholds": null,
			"created": 1588786006,
			"metadata": [],
			"plan": {
			"id": "yearly",
			"object": "plan",
			"active": true,
			"aggregate_usage": null,
			"amount": 8999,
			"amount_decimal": "8999",
			"billing_scheme": "per_unit",
			"created": 1588778754,
			"currency": "usd",
			"interval": "year",
			"interval_count": 1,
			"livemode": false,
			"metadata": [],
			"nickname": "Yearly Plan",
			"product": "prod_HEHhNlSqB7qp6q",
			"tiers": null,
			"tiers_mode": null,
			"transform_usage": null,
			"trial_period_days": 7,
			"usage_type": "licensed"
			},
			"quantity": 1,
			"subscription": "sub_HEJe6gDoQT20Iq",
			"tax_rates": []
			}
			],
			"has_more": false,
			"total_count": 1,
			"url": "/v1/subscription_items?subscription=sub_HEJe6gDoQT20Iq"
			},
			"latest_invoice": "in_1Gfr18GQzApYr7LGgNfGAhFc",
			"livemode": false,
			"metadata": [],
			"next_pending_invoice_item_invoice": null,
			"pause_collection": null,
			"pending_invoice_item_interval": null,
			"pending_setup_intent": null,
			"pending_update": null,
			"plan": {
			"id": "yearly",
			"object": "plan",
			"active": true,
			"aggregate_usage": null,
			"amount": 8999,
			"amount_decimal": "8999",
			"billing_scheme": "per_unit",
			"created": 1588778754,
			"currency": "usd",
			"interval": "year",
			"interval_count": 1,
			"livemode": false,
			"metadata": [],
			"nickname": "Yearly Plan",
			"product": "prod_HEHhNlSqB7qp6q",
			"tiers": null,
			"tiers_mode": null,
			"transform_usage": null,
			"trial_period_days": 7,
			"usage_type": "licensed"
			},
			"quantity": 1,
			"schedule": null,
			"start_date": 1588786005,
			"status": "trialing",
			"tax_percent": null,
			"trial_end": 1589390805,
			"trial_start": 1588786005
			}
			],
			"has_more": false,
			"total_count": 1,
			"url": "/v1/customers/cus_HEJe1orDMGiLrh/subscriptions"
			},
			"tax_exempt": "none",
			"tax_ids": {
			"object": "list",
			"data": [],
			"has_more": false,
			"total_count": 0,
			"url": "/v1/customers/cus_HEJe1orDMGiLrh/tax_ids"
			}
			}');
	}
}
