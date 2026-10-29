<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ValidateSignature;

class ValidateQuizShareSignature extends ValidateSignature
{
    public function handle($request, Closure $next, $relative = null)
    {
        // Social apps can append attribution to an already signed public result.
        // Ignore only inert tracking keys; score, quiz, totals and download stay signed.
        $signed = $request->duplicate();
        $query = array_filter(explode('&', (string) $request->server->get('QUERY_STRING')), function ($part) {
            $key = rawurldecode(explode('=', $part, 2)[0]);
            return !in_array($key, ['fbclid', 'igsh', 'igshid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'], true);
        });
        $signed->server->set('QUERY_STRING', implode('&', $query));

        return parent::handle($signed, function () use ($request, $next) {
            return $next($request);
        }, $relative);
    }
}
