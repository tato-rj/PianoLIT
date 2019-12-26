<?php

namespace App\Listeners;

use App\Events\StudioPolicyCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\NewStudioPolicyNotification;
use App\Mail\NewStudioPolicyEmail;
use App\Admin;

class StudioPolicyCreatedListener
{
    /**
     * Handle the event.
     *
     * @param  StudioPolicyCreated  $event
     * @return void
     */
    public function handle(StudioPolicyCreated $event)
    {
        \Mail::to($event->policy->user->email)->send(new NewStudioPolicyEmail($event->policy));

        Admin::notifyAll(new NewStudioPolicyNotification($event->policy));
    }
}
