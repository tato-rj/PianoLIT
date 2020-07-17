<?php

namespace App\Listeners;

use App\Events\PurchaseMade;
use App\Notifications\NewPurchaseCompleted;
use App\Mail\Shop\ConfirmPurchase;
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
        if (! $event->purchase->item->isFree())
            \Mail::to(auth()->user()->email)->send(new ConfirmPurchase($event->purchase->item));

        Admin::notifyAll(new NewPurchaseCompleted($event->purchase));
    }
}
