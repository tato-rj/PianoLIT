<?php

namespace App\Listeners\Emails;

use App\Events\Emails\RemovedFailed;
use App\Notifications\Subscriptions\FailedSubscriptionsRemoved;
use App\Admin;

class RemovedFailedEmailsListener
{
    /**
     * Handle the event.
     *
     * @param  RemovedFailed  $event
     * @return void
     */
    public function handle(RemovedFailed $event)
    {
        Admin::notifyAll(new FailedSubscriptionsRemoved($event->count));
    }
}
