<?php

namespace App\Listeners\Tutorials;

use App\Events\Tutorials\NewRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Tutorials\NewRequestNotification;
use App\Mail\Tutorials\NewRequestEmail;
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
        \Mail::to($event->request->user->email)->send(new NewRequestEmail($event->request));

        Admin::notifyAll(new NewRequestNotification($event->request));
    }
}
