<?php

namespace App\Listeners\Emails;

use App\Mail\AutomaticEmailSettings;
use Illuminate\Notifications\Events\NotificationSending;

class CheckAutomaticNotification
{
    public function handle(NotificationSending $event)
    {
        if ($event->channel !== 'mail') return;
        $settings = app(AutomaticEmailSettings::class);
        $key = $settings->notificationKey($event->notification);
        if ($key && !$settings->enabled($key)) return false;
    }
}
