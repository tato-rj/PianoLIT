<?php

return [

    'google_analytics' => [
        'measurement_id' => env('GA4_MEASUREMENT_ID', 'G-12WWWZZMJF'),
        'api_secret' => env('GA4_API_SECRET'),
        'api_secret_file' => env('GA4_API_SECRET_FILE', storage_path('app/private/google-analytics-api-secret')),
        'conversions_enabled' => env('GA4_CONVERSIONS_ENABLED', true),
    ],

    'google_login' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        'webapp_redirect_uri' => env('GOOGLE_WEBAPP_REDIRECT_URI'),
        'challenge_minutes' => 15,
        'recent_auth_minutes' => 10,
        'handoff_seconds' => 60,
    ],

    'apple_login' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key_path' => env('APPLE_PRIVATE_KEY_PATH'),
        'redirect_uri' => env('APPLE_REDIRECT_URI'),
        'webapp_redirect_uri' => env('APPLE_WEBAPP_REDIRECT_URI'),
        'transaction_seconds' => 600,
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'max_output_tokens' => env('OPENAI_MAX_OUTPUT_TOKENS', 4096),
    ],

    'chatgpt' => [
        'token' => env('CHATGPT_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'webhook_signing_key' => env('MAILGUN_WEBHOOK_SIGNING_KEY'),
        'webhook_tolerance' => env('MAILGUN_WEBHOOK_TOLERANCE', 86400),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'us_key' => env('STRIPE_US_KEY'),
        'swiss_key' => env('STRIPE_SWISS_KEY'),
        
        'secret' => env('STRIPE_SECRET'),
        'us_secret' => env('STRIPE_US_SECRET'),
        'swiss_secret' => env('STRIPE_SWISS_SECRET'),

        'version' => env('STRIPE_VERSION'),
        // Membership requests use current payment APIs; legacy shop requests keep
        // their own version. Dashboard webhook versions are upgraded separately.
        'membership_version' => '2025-03-31.basil',
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'us_secret' => env('STRIPE_US_WEBHOOK_SECRET'),
            'swiss_secret' => env('STRIPE_SWISS_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    'channels' => [
        'youtube' => 'https://www.youtube.com/pianolit',
        'facebook' => 'https://www.facebook.com/pianolit',
        'reddit' => 'https://www.reddit.com/user/pianolit',
        'twitter' => 'https://twitter.com/LitPiano',
        'pinterest' => 'https://www.pinterest.com/pianolit/',
        'instagram' => 'https://www.instagram.com/pianolit_music/'
    ],

    'recaptcha' => [
        'key' => env('RECAPTCHA_KEY'),
        'secret' => env('RECAPTCHA_SECRET'),
    ],

    'googlecloud' => [
        'videos' => env('GOOGLE_CLOUD_URL'),
        'crashcourses' => 'https://storage.googleapis.com/pianolit-app/crashcourses/'
    ]

];
