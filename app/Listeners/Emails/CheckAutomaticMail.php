<?php

namespace App\Listeners\Emails;

use App\Mail\AutomaticEmailSettings;
use Illuminate\Mail\Events\MessageSending;

class CheckAutomaticMail
{
    public function handle(MessageSending $event)
    {
        $key = $event->data['pianolit_automatic_email'] ?? null;
        if ($key && !app(AutomaticEmailSettings::class)->enabled($key)) return false;
    }
}
