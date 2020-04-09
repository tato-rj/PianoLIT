<?php

namespace App\Listeners\Memberships;

use App\Notifications\Memberships\NewTrialNotification;
use App\Events\Memberships\NewTrial;
use App\Admin;

class NewTrialListener
{
    /**
     * Handle the event.
     *
     * @param  NewTrial  $event
     * @return void
     */
    public function handle(NewTrial $event)
    {
        Admin::notifyAll(new NewTrialNotification($event->user));
    }
}
