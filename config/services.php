<?php

return [

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
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
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
        'videos' => 'https://storage.googleapis.com/pianolit-app/videos/',
        'crashcourses' => 'https://storage.googleapis.com/pianolit-app/crashcourses/'
    ]

];
