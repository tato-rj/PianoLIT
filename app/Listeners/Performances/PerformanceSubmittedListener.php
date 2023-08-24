<?php

namespace App\Listeners\Performances;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Notifications\Performances\PerformanceSubmittedNotification;
use App\Admin;
use App\Mail\Performances\PerformanceSubmittedEmail;

class PerformanceSubmittedListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        \Mail::to(auth()->user()->email)->send(new PerformanceSubmittedEmail($event->performance));

        Admin::notifyAll(new PerformanceSubmittedNotification($event->performance));
    }
}
