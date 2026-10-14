<?php

namespace App\Http\Middleware;

use App\Activity\ExcludedAccounts;
use Closure;

class ExcludeAnalyticsAccounts
{
    public function handle($request, Closure $next)
    {
        ExcludedAccounts::synchronize($request);
        return $next($request);
    }
}
