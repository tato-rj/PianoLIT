<?php

namespace App\Http\Middleware\Logs;

use Closure;
use App\Log\Loggers\AppLog;

class RecordAppLog
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! auth()->guard('admin')->check() && $request->has('user_id'))
            (new AppLog)->push();

        if (testing())
            return redirect('/');

        return $next($request);
    }
}
