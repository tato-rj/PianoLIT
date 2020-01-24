<?php

namespace App\Http\Middleware\Webhooks;

use Closure;

class MailgunWebhook
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
        if (!$request->isMethod('post')) {
            abort(Response::HTTP_FORBIDDEN, 'Only POST requests are allowed.');
        }

        if ($this->verify($request)) {
            return $next($request);
        }

        abort(Response::HTTP_FORBIDDEN);
    }

    private function buildSignature($request)
    {
        return hash_hmac(
            'sha256',
            sprintf('%s%s', $request->input('timestamp'), $request->input('token')),
            config('services.mailgun.secret')
        );
    }

    public function verify($request)
    {
        $token = $request->input('signature.token');
        $timestamp = $request->input('signature.timestamp');
        $signature = $request->input('signature.signature');

        if (abs(time() - $timestamp) > 15) {
            return false;
        }

        return hash_hmac('sha256', $timestamp . $token, config('services.mailgun.secret')) === $signature;
    }
}
