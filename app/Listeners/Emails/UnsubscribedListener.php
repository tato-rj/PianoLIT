<?php

namespace App\Listeners\Emails;

use App\Events\Emails\Unsubscribed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Emails\UnsubscribedNotification;
use App\Admin;

class UnsubscribedListener
{
    /**
     * Handle the event.
     *
     * @param  Unsubscribed  $event
     * @return void
     */
    public function handle(Unsubscribed $event)
    {
        Admin::notifyAll(new UnsubscribedNotification($event->list, $event->subscription));
    }
}
