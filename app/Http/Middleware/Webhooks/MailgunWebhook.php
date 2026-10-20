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
            abort(404, 'Only POST requests are allowed.');
        }

        if ($this->verify($request)) {
            return $next($request);
        }

        abort(404);
    }

    public function verify($request)
    {
        // Read the body only; query parameters must never substitute signed fields.
        $payload = $request->isJson() ? json_decode($request->getContent(), true) : $request->request->all();
        if (! is_array($payload)) return false;
        $token = data_get($payload, 'signature.token');
        $timestamp = data_get($payload, 'signature.timestamp');
        $signature = data_get($payload, 'signature.signature');
        // Legacy accounts used their API secret for signing. An explicit signing
        // key takes precedence, without changing the working sending credential.
        $key = config('services.mailgun.webhook_signing_key') ?: config('services.mailgun.secret');

        if (! is_string($key) || $key === '' || ! is_string($token) || strlen($token) < 1 || strlen($token) > 255
            || ! (is_string($timestamp) || is_int($timestamp)) || ! ctype_digit((string) $timestamp)
            || strlen((string) $timestamp) > 11 || ! is_string($signature) || ! preg_match('/\A[a-f0-9]{64}\z/i', $signature)) {
            return false;
        }

        if ((int) $timestamp > time() + 300
            || time() - (int) $timestamp > (int) config('services.mailgun.webhook_tolerance', 86400)) return false;

        if (! hash_equals(hash_hmac('sha256', $timestamp.$token, $key), strtolower($signature))) return false;
        $request->attributes->set('mailgun_payload', $payload);
        return true;
    }
}
