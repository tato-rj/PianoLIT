<?php

namespace App\Http\Middleware;

use Closure;
use App\Tools\Traffic;

////////////////////////////////////////
// DELETE THIS ON LAUNCH DAY JUNE 1ST //
////////////////////////////////////////

class WebAppCountdown
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
        if ($this->isCountdown($request))
            return $next($request);
        
        return redirect(route('webapp.countdown'));
    }

    public function isCountdown($request)
    {
        return $request->route()->getName() == 'webapp.countdown';
    }
}
