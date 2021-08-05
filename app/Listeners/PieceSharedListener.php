<?php

namespace App\Listeners;

use App\Events\PieceShared;
use App\Admin;
use App\Mail\SharePieceEmail;
use App\Notifications\PieceSharedNotification;

class PieceSharedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  PieceShared  $event
     * @return void
     */
    public function handle(PieceShared $event)
    {
        \Mail::to($event->recipient)->send(new SharePieceEmail($event->piece, auth()->user()));

        Admin::notifyAll(new PieceSharedNotification($event->piece));
    }
}
