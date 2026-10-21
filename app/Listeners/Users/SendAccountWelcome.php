<?php

namespace App\Listeners\Users;

use App\Mail\{AccountWelcome, AutomaticEmailSettings, IosAccountWelcome, WebsiteAccountWelcome};
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
        } elseif ($event->user->origin === 'web') {
            $key = 'account-welcome-website';
            $mailable = WebsiteAccountWelcome::class;
        } elseif ($event->user->origin === 'webapp') {
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
