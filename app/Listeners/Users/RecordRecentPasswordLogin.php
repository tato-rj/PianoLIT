<?php

namespace App\Listeners\Users;

use App\Auth\Google\Accounts;
use Illuminate\Auth\Events\Login;

class RecordRecentPasswordLogin
{
    public function handle(Login $event)
    {
        $request = request();
        if ($event->guard === 'web' && $event->user instanceof \App\User && $request->hasSession()
            && $request->isMethod('POST') && $request->routeIs('login')) {
            (new Accounts)->verified($request, $event->user);
        }
    }
}
