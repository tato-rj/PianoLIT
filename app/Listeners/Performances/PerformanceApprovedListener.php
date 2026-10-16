<?php

namespace App\Listeners\Performances;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Mail\Performances\PerformanceApprovedEmail;

class PerformanceApprovedListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        if (app(\App\Mail\AutomaticEmailSettings::class)->enabled('performance-approved')) {
            \Mail::to(auth()->user()->email)->send(new PerformanceApprovedEmail($event->performance->piece));
        }
    }
}
