<?php

namespace App\Listeners;

use App\Events\eScoreGenerated;
use App\Notifications\eScoreGeneratedNotification;
use App\Admin;

class eScoreGeneratedListener
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\eScoreGenerated  $event
     * @return void
     */
    public function handle(eScoreGenerated $event)
    {
        Admin::notifyAll(new eScoreGeneratedNotification($event->user, $event->folder));
    }
}
