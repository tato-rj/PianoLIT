<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\{Registered, Verified};
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\{StudioPolicyCreated};
use App\Listeners\{SubscribeUser, StudioPolicyCreatedListener};
use App\Listeners\Users\EmailVerified;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            SubscribeUser::class,
        ],
        Verified::class => [
            EmailVerified::class,
        ],
        StudioPolicyCreated::class => [
            StudioPolicyCreatedListener::class,
        ],
        \App\Events\Tutorials\NewRequest::class => [
            \App\Listeners\Tutorials\NewRequestListener::class,
        ],
        \App\Events\Tutorials\RequestPublished::class => [
            \App\Listeners\Tutorials\RequestPublishedListener::class,
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
