<?php

namespace App\Listeners;

use App\Events\PurchaseMade;
use App\Notifications\NewPurchaseCompleted;
use App\Mail\NewStudioPolicyEmail;
use App\Admin;

class NewPurchaseListener
{
    /**
     * Handle the event.
     *
     * @param  PurchaseMade  $event
     * @return void
     */
    public function handle(PurchaseMade $event)
    {
        Admin::notifyAll(new NewPurchaseCompleted($event->purchase));
    }
}
