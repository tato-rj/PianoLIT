<?php

namespace App\Http\Middleware;

use Closure;
use App\Tools\Traffic;

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
        if ($this->isCountdown($request) || $this->isMe())
            return $next($request);
        
        return redirect(route('webapp.countdown'));
    }

    public function isMe()
    {
        return auth()->check() && auth()->user()->id == 928;
    }

    public function isCountdown($request)
    {
        return $request->route()->getName() == 'webapp.countdown';
    }
}
