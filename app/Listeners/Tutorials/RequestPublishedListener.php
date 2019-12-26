<?php

namespace App\Listeners\Tutorials;

use App\Events\Tutorials\RequestPublished;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Tutorials\RequestPublishedNotification;
use App\Mail\Tutorials\RequestPublishedEmail;
use App\Admin;

class RequestPublishedListener
{
    /**
     * Handle the event.
     *
     * @param  RequestPublished  $event
     * @return void
     */
    public function handle(RequestPublished $event)
    {
        \Mail::to($event->request->user->email)->send(new RequestPublishedEmail($event->request));

        Admin::notifyAll(new RequestPublishedNotification($event->request));
    }
}
