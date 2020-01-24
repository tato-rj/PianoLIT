<?php

namespace App\Listeners\Emails;

use App\Events\Emails\EmailListSent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Emails\EmailListSentNotification;
use App\Admin;

class EmailListSentListener
{
    /**
     * Handle the event.
     *
     * @param  EmailListSent  $event
     * @return void
     */
    public function handle(EmailListSent $event)
    {
        Admin::notifyAll(new EmailListSentNotification($event->list));
    }
}
