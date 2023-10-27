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
        ],
        \App\Events\Emails\EmailListSent::class => [
            \App\Listeners\Emails\EmailListSentListener::class
        ],
        \App\Events\Emails\Unsubscribed::class => [
            \App\Listeners\Emails\UnsubscribedListener::class
        ],
        \App\Events\Emails\RemovedFailed::class => [
            \App\Listeners\Emails\RemovedFailedEmailsListener::class
        ],
        \App\Events\CrashCourses\CrashCourseSignUp::class => [
            \App\Listeners\CrashCourses\CrashCourseSignUpListener::class
        ],
        \App\Events\CrashCourses\CrashCourseCancelled::class => [
            \App\Listeners\CrashCourses\CrashCourseCancelledListener::class
        ],
        \App\Events\CrashCourses\CrashCourseFinished::class => [
            \App\Listeners\CrashCourses\CrashCourseFinishedListener::class
        ],
        \App\Events\Memberships\NewTrial::class => [
            \App\Listeners\Memberships\NewTrialListener::class
        ],
        \App\Events\PurchaseMade::class => [
            \App\Listeners\NewPurchaseListener::class
        ],
        \App\Events\PieceShared::class => [
            \App\Listeners\PieceSharedListener::class
        ],
        \App\Events\Performances\PerformanceSubmitted::class => [
            \App\Listeners\Performances\PerformanceSubmittedListener::class
        ],
        \App\Events\Performances\PerformanceApproved::class => [
            \App\Listeners\Performances\PerformanceApprovedListener::class
        ],
        \App\Events\eScoreGenerated::class => [
            \App\Listeners\eScoreGeneratedListener::class
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
