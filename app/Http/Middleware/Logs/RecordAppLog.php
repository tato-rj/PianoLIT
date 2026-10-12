<?php

namespace App\Http\Middleware\Logs;

use Closure;
use App\Log\Loggers\AppLog;
use App\Tools\Traffic;

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
        if ($this->shouldContinue($request)) {
            try {
                (new AppLog)->push();
            } catch (\Throwable $exception) {
                // An analytics outage must not change the mobile response.
                report($exception);
            }
        }

        return $next($request);
    }

    public function shouldContinue($request)
    {
        $isNotAdmin = ! auth()->guard('admin')->check();
        $id = $request->input('user_id');
        $isUser = (is_int($id) || is_string($id)) && preg_match('/^[1-9]\d*$/', (string) $id)
            && (float) $id <= 4294967295 && (new Traffic)->isRealUser($id);
        
        return $isNotAdmin && $isUser;
    }
}
