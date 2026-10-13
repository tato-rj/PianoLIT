<?php

namespace App\Listeners\Users;

use App\Activity\Actions;
use Illuminate\Auth\Events\{Login, Registered};

class RecordAuthentication
{
    public function handle($event)
    {
        $request = request();
        if (!$request->hasSession() || ($event instanceof Login && $event->guard !== 'web') || !($event->user instanceof \App\User)) return;
        if (!(new \App\Activity\Recorder)->eligible($request)) return;
        $type = $event instanceof Registered ? 'registration_completed' : 'login';
        Actions::linkAnonymous($request, $event->user->id, $type);
        Actions::record($request, $type, [], 'user', $event->user->id, null, $event->user->id);
    }
}
