<?php

namespace App\Listeners;

use App\Events\PurchaseMade;
use App\Notifications\NewPurchaseCompleted;
use App\Mail\Shop\ConfirmPurchase;
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
        $owner = $event->purchase->user;
        if ($owner && ! $event->purchase->item->isFree() && app(\App\Mail\AutomaticEmailSettings::class)->enabled('purchase-confirmation'))
            \Mail::to($owner->email)->send(new ConfirmPurchase($event->purchase->item, $owner));

        Admin::notifyAll(new NewPurchaseCompleted($event->purchase));
    }
}
