<?php

namespace App\Listeners\Users;

use App\Mail\{AccountWelcome, AutomaticEmailSettings};
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;

class SendAccountWelcome
{
    public function handle(Registered $event)
    {
        if (!$event->user instanceof User) return;

        try {
            if (app(AutomaticEmailSettings::class)->enabled('account-welcome')) {
                Mail::to($event->user->email)->queue(new AccountWelcome($event->user->first_name ?: 'there'));
            }
        } catch (\Throwable $exception) {
            // An optional welcome must never undo a successful account registration.
            report($exception);
        }
    }
}
