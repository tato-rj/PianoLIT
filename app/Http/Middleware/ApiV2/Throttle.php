<?php

namespace App\Http\Middleware\ApiV2;

use Closure;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Throttle
{
    public function handle($request, Closure $next, $kind = 'read')
    {
        $limiter = app(RateLimiter::class);
        $ip = hash('sha256', (string) $request->ip());
        $keys = ['api.v2.requests.'.$ip => max(1, (int) config('api-v2.requests_per_ip', 120))];
        if ($kind === 'login') {
            $email = $request->input('email');
            $email = is_string($email) ? mb_strtolower(trim($email)) : '';
            $keys = [
                'api.v2.login.ip.'.$ip => max(1, (int) config('api-v2.login_attempts_per_ip', 20)),
                'api.v2.login.account.'.hash('sha256', $email.'|'.$ip) => max(1, (int) config('api-v2.login_attempts_per_email_ip', 5)),
            ];
        }
        foreach ($keys as $key => $limit) {
            if ($limiter->tooManyAttempts($key, $limit)) {
                throw new HttpException(429, '', null, ['Retry-After' => (string) max(1, $limiter->availableIn($key))]);
            }
        }
        foreach ($keys as $key => $limit) $limiter->hit($key, 60);
        return $next($request);
    }
}
