<?php

namespace App\Listeners\SynthesiaRequests;

use App\Events\SynthesiaRequests\NewRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\SynthesiaRequests\NewRequestNotification;
use App\Mail\SynthesiaRequests\NewRequestEmail;
use App\Admin;

class NewRequestListener
{
    /**
     * Handle the event.
     *
     * @param  NewRequest  $event
     * @return void
     */
    public function handle(NewRequest $event)
    {
        if (app(\App\Mail\AutomaticEmailSettings::class)->enabled('synthesia-request')) {
            \Mail::to($event->request->user->email)->send(new NewRequestEmail($event->request));
        }

        Admin::notifyAll(new NewRequestNotification($event->request));
    }
}
