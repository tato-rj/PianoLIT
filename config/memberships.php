<?php

return [

    // Existing Plan IDs also identify Stripe Prices. Set these only when an
    // account uses newer price_ IDs; existing subscriptions are never recreated.
    'stripe_prices' => [
        'us' => ['monthly' => env('STRIPE_US_MONTHLY_PRICE'), 'yearly' => env('STRIPE_US_YEARLY_PRICE')],
        'swiss' => ['monthly' => env('STRIPE_SWISS_MONTHLY_PRICE'), 'yearly' => env('STRIPE_SWISS_YEARLY_PRICE')],
    ],

	'fees' => [
		'monthly' => 7.99,
		'yearly' => 89.99
	]

];
