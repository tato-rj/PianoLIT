<?php

return [
    // Local uses Stripe test payments and simulated fulfillment. Live checkout is opt-in.
    'enabled' => env('SHOP_CHECKOUT_ENABLED', false),
    'mode' => env('SHOP_CHECKOUT_MODE', 'sandbox'),
    'stripe_key' => env('SHOP_STRIPE_KEY'),
    'stripe_secret' => env('SHOP_STRIPE_SECRET'),
    'stripe_webhook_secret' => env('SHOP_STRIPE_WEBHOOK_SECRET'),
    'stripe_version' => '2025-03-31.basil',
    'printful_orders_enabled' => env('PRINTFUL_ORDERS_ENABLED', false),
    'printful_webhook_file' => env('PRINTFUL_WEBHOOK_KEYS_FILE', storage_path('app/private/printful-webhook-keys.json')),
    'countries' => [], // Empty means every destination returned by Printful.
    'quote_minutes' => 15,
    'tax_codes' => ['t-shirts' => 'txcd_30011000', 'stickers' => 'txcd_99999999', 'merchandise' => 'txcd_99999999',
        'ebooks' => 'txcd_10302000', 'escores' => 'txcd_10000000'],
];
