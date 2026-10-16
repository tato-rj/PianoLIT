<?php

namespace App\Listeners\Users;

use App\Mail\{AccountWelcome, AutomaticEmailSettings, IosAccountWelcome};
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;

class SendAccountWelcome
{
    public function handle(Registered $event)
    {
        if (!$event->user instanceof User) return;

        // Use the saved signup source, not the browser/device making this request.
        if ($event->user->origin === 'ios') {
            $key = 'account-welcome-ios';
            $mailable = IosAccountWelcome::class;
        } elseif (in_array($event->user->origin, ['web', 'webapp'], true)) {
            $key = 'account-welcome';
            $mailable = AccountWelcome::class;
        } else {
            return;
        }

        try {
            if (app(AutomaticEmailSettings::class)->enabled($key)) {
                Mail::to($event->user->email)->queue(new $mailable($event->user->first_name ?: 'there'));
            }
        } catch (\Throwable $exception) {
            // An optional welcome must never undo a successful account registration.
            report($exception);
        }
    }
}
