<?php

namespace App\Http\Middleware\Logs;

use Closure;
use App\User;
use App\Log\Loggers\WebLog;

class RecordWebLog
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
        if (auth()->guard('web')->check())
            (new WebLog)->push();
    
        return $next($request);
    }
}
