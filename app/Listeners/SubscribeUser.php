<?php

namespace App\Listeners;

use Illuminate\Auth\Events\REgistered;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Subscription;

class SubscribeUser
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(Registered $event)
    {
        Subscription::createOrActivate($event->user, $notifyUser = false);
    }
}
